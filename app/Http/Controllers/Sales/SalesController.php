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
use App\Models\Account;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\SalesUser;
use App\Traits\PaginatorTrait;
use Carbon\Carbon;
use App\Services\LeadProductService;

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
                        ->orWhere('sale_id', 'like', "%{$keyword}%")
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
                    'sales_id' => $sale->sale_id,
                    'sold_value' => $sale->grand_total ?? 0,
                    'paid_amount' => $sale->paid ?? 0,
                    'status' => $sale->status ?? 'Pending',
                    'approved_by' => $sale->approved_by,
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
    public function store(Request $request, LeadProductService $leadProductService)
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
                'sales_users' => 'nullable|array',
                'sales_users.*.sales_user_id' => 'required|exists:users,id',
                'sales_users.*.commission_percentage' => 'nullable|numeric|min:0|max:100',
                'sales_users.*.commission_amount' => 'nullable|numeric|min:0',
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
                'sale_id' => Sales::generateNextSaleId($companyId),
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
                'status' => ($request->delivery_date && $request->delivery_date === date('Y-m-d')) ? 'Handovered' : 'Pending',
                'primary_contact_id' => $request->primary_contact_id,
                'created_by' => $authUser->id,
            ]);

            // Accounting: Book Sales Revenue (Accrual Basis)
            if (($sale->grand_total ?? 0) > 0) {
                $receivableAccount = Account::where('company_id', $companyId)
                    ->where('name', 'Accounts Receivable')
                    ->first();

                $revenueAccount = Account::where('company_id', $companyId)
                    ->where('name', 'Sales/Revenue')
                    ->first();

                if ($receivableAccount && $revenueAccount) {
                     Transaction::create([
                        'company_id' => $companyId,
                        'debit_account_id' => $receivableAccount->id, 
                        'credit_account_id' => $revenueAccount->id, 
                        'debit' => $sale->grand_total,
                        'credit' => $sale->grand_total,
                        'description' => 'Sales Invoice for Sales #' . $sale->id,
                        'date' => $sale->sale_date,
                        'transactionable_type' => Sales::class,
                        'transactionable_id' => $sale->id,
                        'created_by' => $authUser->id,
                    ]);
                }
            }

            // Update lead status to Salsed
            if ($lead) {
                $lead->status = 'Salsed';
                $lead->save();
            }

            // Sync/Create Lead Products (without deleting others)
            $leadProducts = $leadProductService->syncLeadProducts($lead, $request->products, $companyId, $authUser->id, false);

            // Create sales_products records
            foreach ($request->products as $index => $productData) {
                $leadProduct = $leadProducts[$index] ?? null;

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
                    'lead_id' => $lead->id,
                    'type' => $leadProduct->type,
                    'property_unit_id' => $leadProduct->property_unit_id,
                    'area_id' => $leadProduct->area_id,
                    'product_category_id' => $leadProduct->product_category_id,
                    'product_sub_category_id' => $leadProduct->product_sub_category_id,
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

                // Update product status to Sold if it's a Property
                if ($leadProduct->type === 'Property' && $leadProduct->product_id) {
                    $productToUpdate = Product::find($leadProduct->product_id);
                    if ($productToUpdate) {
                        $productToUpdate->status = 1; // 1 = Sold
                        $productToUpdate->save();
                    }
                }
            }

            // Create SalesUser records if sales_users array is present and not empty
            if ($request->sales_users && is_array($request->sales_users)) {
                foreach ($request->sales_users as $userData) {
                    $userId = $userData['sales_user_id'] ?? null;
                    if (!$userId) continue;

                    $commissionPercentage = $userData['commission_percentage'] ?? 0;
                    $commissionAmount = $userData['commission_amount'] ?? 0;
                    
                    $commissionType = 'amount';
                    $commissionValue = $commissionAmount;
                    $commission = $commissionAmount;

                    if ($commissionPercentage > 0) {
                        $commissionType = 'percentage';
                        $commissionValue = $commissionPercentage;
                        $commission = ($request->grand_total * $commissionPercentage) / 100;
                    }

                    SalesUser::create([
                        'sales_id' => $sale->id,
                        'user_id' => $userId,
                        'commission_type' => $commissionType,
                        'commission_value' => $commissionValue,
                        'commission' => $commission,
                        'payable_commission' => $commission,
                        'commission_payment_type' => 'full',
                        'paid_commission' => 0,
                        'created_by' => $authUser->id,
                    ]);
                }
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
                'products.propertyUnit:id,uuid,name',
                'products.area:id,uuid,name,text',
                'products.productCategory:id,uuid,name',
                'products.productSubCategory:id,uuid,name',
                'keyContact:id,uuid,name,email,phone,profile_image',
                'payments',
                'payments.paymentReason',
                'payments.bank',
                'salesUsers.user',
            ])
            ->first();

            if (!$sale) {
                return error_response('Sale not found', 404);
            }

            // Get primary contact
            $primaryContact = null;
            if ($sale->lead) {
                // Try to find decision maker from lead contacts
                $decisionMaker = $sale->lead->leadContacts()
                    ->where('is_decision_maker', true)
                    ->with('contact')
                    ->first();
                
                if ($decisionMaker && $decisionMaker->contact) {
                    $primaryContact = $decisionMaker->contact;
                }
            }

            // Fallback to customer's primary contact if no lead decision maker found
            if (!$primaryContact && $sale->customer) {
                $primaryContact = $sale->customer->primaryContact;
            }

            // Format contacts from customer
            $formattedContacts = [];
            if ($sale->customer) {
                // Get all contacts from customer's organization or from lead contacts
                $lead = $sale->lead;
                if ($lead) {
                    $formattedContacts = $lead->leadContacts->map(function ($leadContact) {
                        if (!$leadContact->contact) {
                            return null;
                        }
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
                    })->filter()->values()->toArray();
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
                    'type' => $salesProduct->type,
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

                $productData['product'] = [];
                
                // Add extended fields directly to product object for frontend compatibility
                 if ($salesProduct->propertyUnit) {
                    $productData['product']['property_unit'] = [
                        'id' => $salesProduct->propertyUnit->id,
                        'name' => $salesProduct->propertyUnit->name,
                    ];
                }
                if ($salesProduct->area) {
                    $productData['product']['area'] = [
                        'id' => $salesProduct->area->id,
                        'name' => $salesProduct->area->name,
                         'text' => $salesProduct->area->text,
                    ];
                }
                 if ($salesProduct->productCategory) {
                     // For property, this is the Project
                     // For service, this is the Service Category
                    $productData['product']['project'] = [
                        'id' => $salesProduct->productCategory->id,
                        'name' => $salesProduct->productCategory->name,
                    ];
                     $productData['product']['category'] = [
                        'id' => $salesProduct->productCategory->id,
                        'name' => $salesProduct->productCategory->name,
                    ];
                }
                 if ($salesProduct->productSubCategory) {
                      // For property, this is Layout
                    $productData['product']['layout_type'] = [
                        'id' => $salesProduct->productSubCategory->id,
                        'name' => $salesProduct->productSubCategory->name,
                    ];
                     $productData['product']['sub_category'] = [
                        'id' => $salesProduct->productSubCategory->id,
                        'name' => $salesProduct->productSubCategory->name,
                    ];
                }
                
                $productData['product']['applies_to'] = $salesProduct->type;

                if ($product) {
                    $productData['product'] = array_merge($productData['product'], [
                        'id' => $product->id,
                        'uuid' => $product->uuid,
                        'name' => $product->name,
                        'price' => $product->price,
                        'image' => $product->image,
                        'image_url' => getFileUrl($product->image),
                        'sell_price' => $product->sell_price,
                    ]);
                     // Keep original category/sub_category if present on product, but salesProduct overrides take precedence for specific transaction details?
                     // Actually salesProduct stores the specific variant sold.
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
                'sale_id' => $sale->sale_id,
                'sale_date' => formatDate($sale->sale_date),
                'delivery_date' => formatDate($sale->delivery_date),
                'status' => $sale->status,
                'subtotal' => $sale->subtotal,
                'discount' => $sale->discount,
                'other_price' => $sale->other_price,
                'grand_total' => $sale->grand_total,
                'paid' => $sale->paid,
                'due' => $sale->due,
                'approved_by' => $sale->approved_by,
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
                'key_contact' => $sale->keyContact ? [
                    'id' => $sale->keyContact->id,
                    'uuid' => $sale->keyContact->uuid,
                    'name' => $sale->keyContact->name,
                    'email' => $sale->keyContact->email,
                    'phone' => $sale->keyContact->phone,
                    'profile_image' => $sale->keyContact->profile_image,
                    'profile_image_url' => getFileUrl($sale->keyContact->profile_image),
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
                'paid_history' => $sale->payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'uuid' => $payment->uuid,
                        'amount' => $payment->amount,
                        'payment_date' => formatDate($payment->payment_date),
                        'transaction_ref' => $payment->transaction_ref,
                        'status' => $payment->status, // 0=Pending, 1=Approved, 2=Rejected
                        'notes' => $payment->notes,
                        'payment_reason' => ($payment->schedule && $payment->schedule->paymentReason) ? [
                            'id' => $payment->schedule->paymentReason->id,
                            'name' => $payment->schedule->paymentReason->name ?? $payment->schedule->paymentReason->title,
                        ] : null,
                        'bank' => $payment->bank ? [
                            'id' => $payment->bank->id,
                            'name' => $payment->bank->name,
                            'account_number' => $payment->bank->account_number,
                        ] : null,
                    ];
                }),
                'lead' => $leadData,
                'sales_users' => $sale->salesUsers->map(function ($salesUser) use ($sale) {
                    $paidAmount = $sale->payments->where('status', 1)->sum('amount');
                    $grandTotal = $sale->grand_total;

                    return [
                        'id' => $salesUser->id,
                        'user' => $salesUser->user ? [
                            'id' => $salesUser->user->id,
                            'name' => $salesUser->user->name,
                            'profile_image_url' => getFileUrl($salesUser->user->profile_image),
                        ] : null,
                        'commission_type' => $salesUser->commission_type,
                        'commission_value' => $salesUser->commission_value,
                        'commission' => $salesUser->commission,
                        'payable_commission' => $salesUser->calculatePayable($grandTotal, $paidAmount),
                        'paid_commission' => $salesUser->paid_commission,
                    ];
                }),
            ]);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function approve($uuid)
    {
        try {
            $sale = Sales::where('uuid', $uuid)->firstOrFail();

            if ($sale->approved_by) {
                return error_response('Sale is already approved', 400);
            }

            $sale->approved_by = auth()->id();
            $sale->save();

            return success_response(null, 'Sale approved successfully');
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

