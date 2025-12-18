<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use App\Models\SalesProduct;
use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Contact;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\PaginatorTrait;
use Carbon\Carbon;

class SalesController extends Controller
{
    use PaginatorTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;
            $keyword = $request->keyword;

            // Base query with relations
            $query = Sales::query()
                ->with([
                    'customer.primaryContact:id,uuid,name,phone,profile_image',
                    'salesBy:id,uuid,name',
                ])
                ->where('company_id', $companyId);

            // Keyword search
            if ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('id', 'like', "%{$keyword}%")
                        ->orWhereHas('customer.primaryContact', function ($contactQuery) use ($keyword) {
                            $contactQuery->where('name', 'like', "%{$keyword}%")
                                ->orWhere('phone', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('customer', function ($customerQuery) use ($keyword) {
                            $customerQuery->where('customer_code', 'like', "%{$keyword}%");
                        });
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by');
            $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
            $allowedSorts = ['id', 'grand_total', 'paid', 'status', 'sale_date', 'created_at'];

            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                // Default: latest first
                $query->orderBy('created_at', 'desc');
            }

            // Paginate
            $paginated = $this->paginateQuery($query, $request);

            // Map response
            $paginated['data'] = collect($paginated['data'])->map(function ($sale) {
                $contact = $sale->customer?->primaryContact;
                
                return [
                    'id' => $sale->id,
                    'uuid' => $sale->uuid,
                    'sales_id' => $sale->id, // Using id as sales_id
                    'sold_value' => $sale->grand_total ?? 0,
                    'paid_amount' => $sale->paid ?? 0,
                    'status' => $sale->status ?? 'pending',
                    'contact' => $contact ? [
                        'uuid' => $contact->uuid,
                        'name' => $contact->name,
                        'phone' => $contact->phone,
                        'profile_image_url' => getFileUrl($contact->profile_image),
                    ] : null,
                    'customer' => $sale->customer ? [
                        'id' => $sale->customer->id,
                        'uuid' => $sale->customer->uuid,
                        'customer_code' => $sale->customer->customer_code,
                    ] : null,
                    'sales_by' => $sale->salesBy ? [
                        'id' => $sale->salesBy->id,
                        'uuid' => $sale->salesBy->uuid,
                        'name' => $sale->salesBy->name,
                    ] : null,
                    'sale_date' => formatDate($sale->sale_date),
                    'delivery_date' => formatDate($sale->delivery_date),
                    'created_at' => formatDate($sale->created_at),
                ];
            });

            return success_response($paginated);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            // Validate required fields
            $request->validate([
                'lead_id' => 'required|exists:leads,id',
                'primary_contact_id' => 'required|exists:contacts,id',
                'sale_date' => 'required|date',
                'products' => 'required|array|min:1',
                'products.*.category' => 'required|in:Property,Service',
                'products.*.quantity' => 'required|integer|min:1',
                'products.*.other_price' => 'nullable|numeric|min:0',
                'products.*.discount' => 'nullable|numeric|min:0',
            ]);

            // Get lead with relationships
            $lead = Lead::where('id', $request->lead_id)
                ->where('company_id', $companyId)
                ->with(['products', 'leadContacts'])
                ->first();

            if (!$lead) {
                return error_response('Lead not found', 404);
            }

            // Check if lead has customer_id
            $customerId = $lead->customer_id;
            
            if (!$customerId) {
                // Create customer if not exists
                $primaryContact = Contact::where('id', $request->primary_contact_id)
                    ->where('company_id', $companyId)
                    ->first();

                if (!$primaryContact) {
                    return error_response('Primary contact not found', 404);
                }

                $customer = Customer::create([
                    'company_id' => $companyId,
                    'customer_code' => Customer::generateNextCustomerId(),
                    'organization_id' => $lead->organization_id,
                    'primary_contact_id' => $request->primary_contact_id, 
                    'total_sales' => 1,
                    'total_sales_amount' => $request->grand_total ?? 0,
                    'created_by' => $authUser->id,
                ]);

                $customerId = $customer->id;

                // Update lead with customer_id
                $lead->customer_id = $customerId;
                $lead->save();
            } else {
                // Increment customer sales count and amount
                $customer = Customer::where('id', $customerId)
                    ->where('company_id', $companyId)
                    ->first();

                if ($customer) {
                    $customer->increment('total_sales');
                    $customer->increment('total_sales_amount', $request->grand_total ?? 0);
                }
            }

            // Create sales record
            $sale = Sales::create([
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'lead_id' => $lead->id,
                'organization_id' => $lead->organization_id,
                'campaign_id' => $lead->campaign_id,
                'sale_type' => 'sell',
                'sales_by' => $authUser->id,
                'subtotal' => $request->subtotal ?? 0,
                'discount' => $request->discount ?? 0,
                'other_price' => $request->other_price ?? 0,
                'grand_total' => $request->grand_total ?? 0,
                'paid' => 0,
                'due' => $request->grand_total ?? 0,
                'sale_date' => $request->sale_date,
                'delivery_date' => $request->delivery_date ?? null,
                'status' => 'pending',
                'created_by' => $authUser->id,
            ]);

            // Create sales_products records
            foreach ($request->products as $productData) {
                $category = $productData['category'] ?? null;
                $leadProductId = $productData['lead_product_id'] ?? null;
                $productId = $productData['product_id'] ?? null;
                $productSubCategoryId = $productData['product_sub_category_id'] ?? null;
                $unitId = $productData['unit_id'] ?? null;
                $layoutId = $productData['layout_id'] ?? null;
                $serviceId = $productData['service_id'] ?? null;
                
                // Map unit_id/layout_id/service_id to product_id/product_sub_category_id (same as lead create)
                if ($category === 'Property') {
                    if ($unitId) {
                        $productId = $unitId; // unit_id is actually product_id
                    } elseif ($layoutId) {
                        $productSubCategoryId = $layoutId; // layout_id is product_sub_category_id
                    }
                } elseif ($category === 'Service') {
                    if ($serviceId) {
                        $productId = $serviceId; // service_id is actually product_id
                    }
                }
                
                // Get lead_product if lead_product_id exists
                $leadProduct = null;
                if ($leadProductId) {
                    $leadProduct = LeadProduct::where('id', $leadProductId)
                        ->where('company_id', $companyId)
                        ->with('product')
                        ->first();
                }

                // If no lead_product_id, try to find or create lead_product
                if (!$leadProduct && $lead->id) {
                    // Try to find existing lead_product by product_id and lead_id
                    if ($productId) {
                        $leadProduct = LeadProduct::where('lead_id', $lead->id)
                            ->where('product_id', $productId)
                            ->where('company_id', $companyId)
                            ->first();
                    }
                    
                    // If still not found, create new lead_product (for new products added in sales modal)
                    if (!$leadProduct) {
                        // Determine product_id and product_sub_category_id similar to lead create
                        if (empty($productId) && !empty($productSubCategoryId)) {
                            $product = Product::where('sub_category_id', $productSubCategoryId)
                                ->where('company_id', $companyId)
                                ->where('applies_to', $category === 'Service' ? 'service' : 'property')
                                ->first();
                            
                            if ($product) {
                                $productId = $product->id;
                            }
                        }

                        // Get service category and sub_category for Service products
                        $serviceCategoryId = null;
                        $serviceSubCategoryId = null;

                        if ($productId && $category === 'Service') {
                            $product = Product::where('id', $productId)
                                ->where('company_id', $companyId)
                                ->first();
                            if ($product) {
                                $serviceCategoryId = $product->category_id ?? null;
                                $serviceSubCategoryId = $product->sub_category_id ?? null;
                            }
                        } elseif ($productId && $category === 'Property' && empty($productSubCategoryId)) {
                            $product = Product::where('id', $productId)
                                ->where('company_id', $companyId)
                                ->first();
                            if ($product) {
                                $productSubCategoryId = $product->sub_category_id ?? null;
                            }
                        }

                        $leadProduct = LeadProduct::create([
                            'company_id' => $companyId,
                            'lead_id' => $lead->id,
                            'type' => $category,
                            'property_unit_id' => $productData['property_unit_id'] ?? null,
                            'area_id' => $productData['area_id'] ?? null,
                            'product_category_id' => $category === 'Service' ? $serviceCategoryId : ($productData['property_id'] ?? null),
                            'product_sub_category_id' => $category === 'Service' ? $serviceSubCategoryId : $productSubCategoryId,
                            'product_id' => $productId,
                            'quantity' => $productData['quantity'] ?? 1,
                            'other_price' => $productData['other_price'] ?? 0,
                            'discount' => $productData['discount'] ?? 0,
                            'notes' => $productData['notes'] ?? null,
                            'created_by' => $authUser->id,
                        ]);
                        // Reload with product relationship
                        $leadProduct->load('product');
                    }
                }

                if (!$leadProduct) {
                    continue;
                }

                // Get product details for fields from products table (lines 22-34)
                $product = null;
                if ($leadProduct->product_id) {
                    // If relationship already loaded, use it, otherwise fetch
                    if ($leadProduct->relationLoaded('product')) {
                        $product = $leadProduct->product;
                    } else {
                        $product = Product::where('id', $leadProduct->product_id)
                            ->where('company_id', $companyId)
                            ->first();
                    }
                }

                // Fields from products table (lines 22-34)
                $rate = $product ? ($product->rate ?? 0) : 0;
                $quantity = $leadProduct->quantity ?? 0;
                $price = $product ? ($product->price ?? 0) : 0;
                $otherPrice = $leadProduct->other_price ?? 0;
                $discount = $leadProduct->discount ?? 0;
                $vatSettingId = $product ? ($product->vat_setting_id ?? null) : null;
                $vatRate = $product ? ($product->vat_rate ?? null) : null;
                $vatAmount = $product ? ($product->vat_amount ?? null) : null;
                $sellPrice = $product ? ($product->sell_price ?? 0) : 0;

                // Fields from frontend form values (lines 36-42) - use form values, not lead_product values
                $orderQuantity = $productData['quantity'] ?? $quantity;
                $orderPrice = $sellPrice; // Base price from product
                $orderOtherPrice = $productData['other_price'] ?? $otherPrice;
                $orderDiscount = $productData['discount'] ?? $discount;
                $orderTotalPrice = ($orderPrice * $orderQuantity) + $orderOtherPrice - $orderDiscount;
                $notes = $productData['notes'] ?? $leadProduct->notes ?? null;

                SalesProduct::create([
                    'company_id' => $companyId,
                    'sales_id' => $sale->id,
                    'product_id' => $leadProduct->product_id ?? null,
                    'rate' => $rate,
                    'quantity' => $quantity,
                    'price' => $price,
                    'other_price' => $otherPrice,
                    'discount' => $discount,
                    'vat_setting_id' => $vatSettingId,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'sell_price' => $sellPrice,
                    'order_quantity' => $orderQuantity,
                    'order_price' => $orderPrice,
                    'order_other_price' => $orderOtherPrice,
                    'order_discount' => $orderDiscount,
                    'order_total_price' => $orderTotalPrice,
                    'notes' => $notes,
                    'created_by' => $authUser->id,
                ]);
            }

            DB::commit();
            return success_response(['uuid' => $sale->uuid], 'Sales created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return error_response('Validation failed', 422, $e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($identifier)
    {
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            // Check if identifier is numeric (id) or UUID string
            $query = Sales::where('company_id', $companyId);
            
            if (is_numeric($identifier)) {
                $query->where('id', $identifier);
            } else {
                $query->where('uuid', $identifier);
            }

            $sale = $query->with([
                'customer.primaryContact:id,uuid,name,phone,email,profile_image',
                'lead:id,uuid,lead_id,organization_id,campaign_id,lead_category_id,lead_source_id,assigned_to,notes,challenges,other_price,discount',
                'lead.leadCategory:id,uuid,title',
                'lead.leadSource:id,uuid,name',
                'lead.campaign:id,uuid,name',
                'lead.organization:id,uuid,name,organization_type,industry,website,address',
                'lead.assignedTo:id,uuid,name,email,phone,profile_image',
                'lead.createdBy:id,uuid,name',
                'lead.updatedBy:id,uuid,name',
                'lead.leadContacts.contact:id,uuid,name,phone,email,profile_image',
                'organization:id,uuid,name,organization_type,industry,website,address',
                'campaign:id,uuid,name',
                'salesBy:id,uuid,name,email,phone,profile_image',
                'createdBy:id,uuid,name',
                'updatedBy:id,uuid,name',
                'products.product:id,uuid,name,price,image,sell_price',
                'products.product.category:id,uuid,name',
                'products.product.subCategory:id,uuid,name',
            ])
            ->first();

            if (!$sale) {
                return error_response('Sale not found', 404);
            }

            // Get primary contact
            $primaryContact = $sale->customer?->primaryContact;

            // Format contacts from customer
            $formattedContacts = [];
            if ($sale->customer) {
                // Get all contacts from customer's organization or from lead contacts
                $lead = $sale->lead;
                if ($lead) {
                    $formattedContacts = $lead->leadContacts->map(function ($leadContact) {
                        return [
                            'id' => $leadContact->contact_id,
                            'uuid' => $leadContact->contact->uuid,
                            'name' => $leadContact->contact->name,
                            'phone' => $leadContact->contact->phone,
                            'email' => $leadContact->contact->email,
                            'profile_image' => $leadContact->contact->profile_image,
                            'profile_image_url' => getFileUrl($leadContact->contact->profile_image),
                            'is_decision_maker' => $leadContact->is_decision_maker,
                            'relationship_or_role' => $leadContact->relationship_or_role,
                            'notes' => $leadContact->notes,
                        ];
                    })->toArray();
                } else {
                    // Fallback: if no lead, use primary contact only
                    if ($primaryContact) {
                        $formattedContacts = [[
                            'id' => $primaryContact->id,
                            'uuid' => $primaryContact->uuid,
                            'name' => $primaryContact->name,
                            'phone' => $primaryContact->phone,
                            'email' => $primaryContact->email,
                            'profile_image' => $primaryContact->profile_image,
                            'profile_image_url' => getFileUrl($primaryContact->profile_image),
                            'is_decision_maker' => true,
                            'relationship_or_role' => 'Primary Contact',
                            'notes' => null,
                        ]];
                    }
                }
            }

            // Format products
            $formattedProducts = $sale->products->map(function ($salesProduct) {
                $product = $salesProduct->product;
                
                $productData = [
                    'id' => $salesProduct->id,
                    'uuid' => $salesProduct->uuid,
                    'order_quantity' => $salesProduct->order_quantity,
                    'order_price' => $salesProduct->order_price,
                    'order_other_price' => $salesProduct->order_other_price,
                    'order_discount' => $salesProduct->order_discount,
                    'order_total_price' => $salesProduct->order_total_price,
                    'quantity' => $salesProduct->quantity,
                    'price' => $salesProduct->price,
                    'other_price' => $salesProduct->other_price,
                    'discount' => $salesProduct->discount,
                    'sell_price' => $salesProduct->sell_price,
                    'notes' => $salesProduct->notes,
                ];

                if ($product) {
                    $productData['product'] = [
                        'id' => $product->id,
                        'uuid' => $product->uuid,
                        'name' => $product->name,
                        'price' => $product->price,
                        'image' => $product->image,
                        'image_url' => getFileUrl($product->image),
                        'sell_price' => $product->sell_price,
                        'category' => $product->category ? [
                            'id' => $product->category->id,
                            'name' => $product->category->name,
                        ] : null,
                        'sub_category' => $product->subCategory ? [
                            'id' => $product->subCategory->id,
                            'name' => $product->subCategory->name,
                        ] : null,
                    ];
                }

                return $productData;
            });

            // Format lead data (same structure as lead details)
            $leadData = null;
            if ($sale->lead) {
                $lead = $sale->lead;
                $leadData = [
                    'id' => $lead->id,
                    'uuid' => $lead->uuid,
                    'lead_id' => $lead->lead_id,
                    'lead_category' => $lead->leadCategory ? [
                        'id' => $lead->leadCategory->id,
                        'uuid' => $lead->leadCategory->uuid,
                        'title' => $lead->leadCategory->title,
                    ] : null,
                    'lead_source' => $lead->leadSource ? [
                        'id' => $lead->leadSource->id,
                        'uuid' => $lead->leadSource->uuid,
                        'name' => $lead->leadSource->name,
                    ] : null,
                    'campaign' => $lead->campaign ? [
                        'id' => $lead->campaign->id,
                        'uuid' => $lead->campaign->uuid,
                        'name' => $lead->campaign->name,
                    ] : null,
                    'organization' => $lead->organization ? [
                        'id' => $lead->organization->id,
                        'uuid' => $lead->organization->uuid,
                        'name' => $lead->organization->name,
                        'organization_type' => $lead->organization->organization_type,
                        'industry' => $lead->organization->industry,
                        'website' => $lead->organization->website,
                        'address' => $lead->organization->address,
                    ] : null,
                    'assigned_to' => $lead->assignedTo ? [
                        'id' => $lead->assignedTo->id,
                        'uuid' => $lead->assignedTo->uuid,
                        'name' => $lead->assignedTo->name,
                        'email' => $lead->assignedTo->email,
                        'phone' => $lead->assignedTo->phone,
                        'profile_image' => $lead->assignedTo->profile_image,
                        'profile_image_url' => getFileUrl($lead->assignedTo->profile_image),
                    ] : null,
                    'created_by' => $lead->createdBy ? [
                        'id' => $lead->createdBy->id,
                        'uuid' => $lead->createdBy->uuid,
                        'name' => $lead->createdBy->name,
                    ] : null,
                    'updated_by' => $lead->updatedBy ? [
                        'id' => $lead->updatedBy->id,
                        'uuid' => $lead->updatedBy->uuid,
                        'name' => $lead->updatedBy->name,
                    ] : null,
                    'notes' => $lead->notes,
                    'challenges' => $lead->challenges,
                    'other_price' => $lead->other_price,
                    'discount' => $lead->discount,
                ];
            }

            return success_response([
                'id' => $sale->id,
                'uuid' => $sale->uuid,
                'sale_id' => $sale->id,
                'sale_date' => formatDate($sale->sale_date),
                'delivery_date' => formatDate($sale->delivery_date),
                'status' => $sale->status,
                'subtotal' => $sale->subtotal,
                'discount' => $sale->discount,
                'other_price' => $sale->other_price,
                'grand_total' => $sale->grand_total,
                'paid' => $sale->paid,
                'due' => $sale->due,
                'primary_contact' => $primaryContact ? [
                    'id' => $primaryContact->id,
                    'uuid' => $primaryContact->uuid,
                    'name' => $primaryContact->name,
                    'phone' => $primaryContact->phone,
                    'email' => $primaryContact->email,
                    'profile_image' => $primaryContact->profile_image,
                    'profile_image_url' => getFileUrl($primaryContact->profile_image),
                ] : null,
                'customer' => $sale->customer ? [
                    'id' => $sale->customer->id,
                    'uuid' => $sale->customer->uuid,
                    'customer_code' => $sale->customer->customer_code,
                ] : null,
                'organization' => $sale->organization ? [
                    'id' => $sale->organization->id,
                    'uuid' => $sale->organization->uuid,
                    'name' => $sale->organization->name,
                    'organization_type' => $sale->organization->organization_type,
                    'industry' => $sale->organization->industry,
                    'website' => $sale->organization->website,
                    'address' => $sale->organization->address,
                ] : null,
                'campaign' => $sale->campaign ? [
                    'id' => $sale->campaign->id,
                    'uuid' => $sale->campaign->uuid,
                    'name' => $sale->campaign->name,
                ] : null,
                'sales_by' => $sale->salesBy ? [
                    'id' => $sale->salesBy->id,
                    'uuid' => $sale->salesBy->uuid,
                    'name' => $sale->salesBy->name,
                    'email' => $sale->salesBy->email,
                    'phone' => $sale->salesBy->phone,
                    'profile_image' => $sale->salesBy->profile_image,
                    'profile_image_url' => getFileUrl($sale->salesBy->profile_image),
                ] : null,
                'created_by' => $sale->createdBy ? [
                    'id' => $sale->createdBy->id,
                    'uuid' => $sale->createdBy->uuid,
                    'name' => $sale->createdBy->name,
                ] : null,
                'created_at' => formatDate($sale->created_at),
                'contacts' => $formattedContacts,
                'products' => $formattedProducts,
                'lead' => $leadData,
            ]);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // TODO: Implement update method if needed
        return error_response('Not implemented', 501);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($uuid)
    {
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            $sale = Sales::where('uuid', $uuid)
                ->where('company_id', $companyId)
                ->first();

            if (!$sale) {
                return error_response('Sale not found', 404);
            }

            $sale->delete();

            return success_response(null, 'Sale deleted successfully');
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
}

