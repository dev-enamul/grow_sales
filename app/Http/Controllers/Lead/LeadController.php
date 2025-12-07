<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadStoreRequest;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\LeadCategory;
use App\Models\LeadContact;
use App\Models\LeadProduct;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductSubCategory;
use App\Models\ProductCategory;
use App\Models\Sales;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Traits\PaginatorTrait;

class LeadController extends Controller
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
            $category = $request->category_id;
            $status = $request->status ?? "Active";
            $selectOnly = $request->boolean('select');
            $keyword = $request->keyword;

            // Base query with relations - only necessary fields for table
            $query = Lead::query()
                ->with([
                    'leadContacts.contact:id,uuid,name,phone,profile_image',
                    'assignedTo:id,uuid,name',
                    'leadCategory:id,uuid,title',
                    'products:id,lead_id',
                ])
                ->where('company_id', $companyId)
                ->where('status', $status)
                ->when($category, function ($q) use ($category) {
                    $q->where('lead_category_id', $category);
                });

            // Keyword search
            if ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('lead_id', 'like', "%{$keyword}%")
                        ->orWhere('notes', 'like', "%{$keyword}%")
                        ->orWhereHas('leadContacts.contact', function ($contactQuery) use ($keyword) {
                            $contactQuery->where('name', 'like', "%{$keyword}%")
                                ->orWhere('phone', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('assignedTo', function ($userQuery) use ($keyword) {
                            $userQuery->where('name', 'like', "%{$keyword}%");
                        });
                });
            }

            // Select only minimal set for dropdowns
            if ($selectOnly) {
                $items = $query
                    ->select('id', 'uuid', 'lead_id')
                    ->latest()
                    ->take(10)
                    ->get()
                    ->map(function ($lead) {
                        $primaryContact = $lead->leadContacts->where('is_decision_maker', true)->first()?->contact 
                            ?? $lead->leadContacts->first()?->contact;
                        return [
                            'id' => $lead->id,
                            'name' => $primaryContact?->name ?? $lead->lead_id,
                        ];
                    });
                return success_response($items);
            }

            // Sorting
            $sortBy = $request->input('sort_by');
            $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
            $allowedSorts = ['lead_id', 'next_followup_date', 'last_contacted_at', 'created_at'];

            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                if ($sortBy === 'next_followup_date') {
                    $query->orderByRaw('next_followup_date IS NULL DESC, next_followup_date ' . $sortOrder);
                } else {
                    $query->orderBy($sortBy, $sortOrder);
                }
            } else {
                // Default: next_followup_date (nulls last), then latest
                $query->orderByRaw('next_followup_date IS NULL DESC, next_followup_date DESC');
            }

            // Paginate
            $paginated = $this->paginateQuery($query, $request);

            // Map response - only necessary fields for table
            $paginated['data'] = collect($paginated['data'])->map(function ($lead) {
                $primaryContact = $lead->leadContacts->where('is_decision_maker', true)->first()?->contact 
                    ?? $lead->leadContacts->first()?->contact;
                
                return [
                    'id' => $lead->id,
                    'uuid' => $lead->uuid,
                    'lead_id' => $lead->lead_id,
                    'contact' => $primaryContact ? [
                        'uuid' => $primaryContact->uuid,
                        'name' => $primaryContact->name,
                        'phone' => $primaryContact->phone,
                        'profile_image_url' => getFileUrl($primaryContact->profile_image),
                    ] : null,
                    'assigned_to' => $lead->assignedTo ? [
                        'id' => $lead->assignedTo->id,
                        'uuid' => $lead->assignedTo->uuid,
                        'name' => $lead->assignedTo->name,
                    ] : null,
                    'lead_category' => $lead->leadCategory ? [
                        'id' => $lead->leadCategory->id,
                        'uuid' => $lead->leadCategory->uuid,
                        'name' => $lead->leadCategory->title,
                    ] : null,
                    'status' => $lead->status,
                    'next_followup_date' => formatDate($lead->next_followup_date),
                    'last_contacted_at' => formatDate($lead->last_contacted_at),
                    'notes' => $lead->notes,
                    'challenges' => $lead->challenges,
                    'products_count' => $lead->products->count(),
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
    public function store(LeadStoreRequest $request)
    {
        DB::beginTransaction();
        try{
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            // Get default lead category
            $lead_category = LeadCategory::where('status', 1)
                ->where('company_id', $companyId)
                ->orderBy('serial', 'asc')->first(); 

            $contact = Contact::where('id', $request->contact_id)->first();
            
            if (!$contact) {
                DB::rollBack();
                return error_response('Contact not found', 404);
            }
            
            // Create lead (company_id is set automatically by ActionTrackable trait)
            $lead = Lead::create([
                'company_id' => $companyId,
                'lead_id' => Lead::generateNextLeadId(),
                'organization_id' => $contact->organization_id ?? null,
                'lead_category_id' => $lead_category?->id ?? null,
                'last_contacted_at' => now(),
                'next_followup_date' => $request->next_followup_date ?: Carbon::now()->addDay(7),
                'assigned_to' => $request->assigned_to?? $authUser->id,
                'lead_source_id' => $request->lead_source_id?? null,
                'campaign_id' => $request->campaign_id?? null,
                'challenges' => $request->challenges && is_array($request->challenges) ? json_encode($request->challenges) : null,
                'notes' => $request->notes?? null,
                'created_by' => $authUser->id,
            ]); 

            LeadContact::create([
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'contact_id' => $contact->id,
                'relationship_or_role' => "Customer",
                'is_decision_maker' => true,
                'created_by' => $authUser->id,
            ]); 

            // Save lead products
            if ($request->products && is_array($request->products)) {
                foreach ($request->products as $productData) {
                    $category = $productData['category'] ?? null;
                    $productId = $productData['product_id'] ?? null;
                    $productSubCategoryId = $productData['product_sub_category_id'] ?? null;
                    $negotiatedPrice = $productData['negotiated_price'] ?? $productData['negotiation_price'] ?? null;
                   
                    // If product_id is not provided but product_sub_category_id is, find a product with that sub_category
                    if (empty($productId) && !empty($productSubCategoryId)) {
                        $product = Product::where('sub_category_id', $productSubCategoryId)
                            ->where('company_id', $companyId)
                            ->where('applies_to', $category === 'Service' ? 'service' : 'property')
                            ->first();
                        
                        if ($product) {
                            $productId = $product->id;
                        } 
                    }

                    // For Service category, product_id is required
                    if ($category === 'Service' && empty($productId)) {
                        DB::rollBack();
                        return error_response('Product ID is required for Service category.', 400);
                    }

                    // Prepare data for LeadProduct (without qty, price, subtotal, vat_rate, vat_value, discount, grand_total)
                    $leadProductData = [
                        'company_id' => $companyId,
                        'lead_id' => $lead->id,
                        'type' => $category,
                        'property_unit_id' => $productData['property_unit_id'] ?? null,
                        'area_id' => $productData['area_id'] ?? null,
                        'product_category_id' => $productData['property_id'] ?? null,
                        'product_sub_category_id' => $productSubCategoryId,
                        'product_id' => $productId,
                        'negotiated_price' => $negotiatedPrice,
                        'created_by' => $authUser->id,
                    ];

                    LeadProduct::create($leadProductData);
                }
            }

            DB::commit();
            return success_response(null, "Lead created successfully");
        }catch(Exception $e){ 
            DB::rollBack();
            return error_response($e->getMessage());
        } 

    }  


    /**
     * Display the specified resource.
     */
    public function show($uuid)
    {
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            $lead = Lead::where('uuid', $uuid)
                ->where('company_id', $companyId)
                ->with([
                    'leadCategory:id,uuid,title',
                    'leadSource:id,uuid,name',
                    'campaign:id,uuid,name',
                    'organization:id,uuid,name,organization_type,industry,website,address',
                    'assignedTo:id,uuid,name,email,phone,profile_image',
                    'createdBy:id,uuid,name',
                    'updatedBy:id,uuid,name',
                    'leadContacts.contact:id,uuid,name,phone,email,profile_image',
                    'products.product:id,uuid,name,price,image',
                    'products.product.category:id,uuid,name',
                    'products.product.subCategory:id,uuid,name',
                    'followups.leadCategory:id,uuid,title',
                    'followups.createdBy:id,uuid,name',
                ])
                ->first();

            if (!$lead) {
                return error_response('Lead not found', 404);
            }

            // Get primary contact (decision maker or first contact)
            $primaryContact = $lead->leadContacts->where('is_decision_maker', true)->first()?->contact 
                ?? $lead->leadContacts->first()?->contact;

            // Format contacts
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
            });

            // Format products
            $formattedProducts = $lead->products->map(function ($leadProduct) {
                $product = $leadProduct->product;
                $productData = [
                    'id' => $leadProduct->id,
                    'uuid' => $leadProduct->uuid,
                    'category' => $leadProduct->type,
                    'negotiated_price' => $leadProduct->negotiated_price,
                    'negotiation_price' => $leadProduct->negotiated_price,
                    'qty' => $leadProduct->qty,
                    'price' => $leadProduct->price,
                    'subtotal' => $leadProduct->subtotal,
                    'vat_rate' => $leadProduct->vat_rate,
                    'vat_value' => $leadProduct->vat_value,
                    'discount' => $leadProduct->discount,
                    'grand_total' => $leadProduct->grand_total,
                    'notes' => $leadProduct->notes,
                ];

                if ($product) {
                    $productData['product'] = [
                        'id' => $product->id,
                        'uuid' => $product->uuid,
                        'name' => $product->name,
                        'price' => $product->price,
                        'image' => $product->image,
                        'image_url' => getFileUrl($product->image),
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

                if ($leadProduct->type === 'Property') {
                    $productData['property_unit_id'] = $leadProduct->property_unit_id;
                    $productData['area_id'] = $leadProduct->area_id;
                    $productData['property_id'] = $leadProduct->product_category_id;
                    $productData['layout_id'] = $leadProduct->product_sub_category_id;
                    $productData['unit_id'] = $leadProduct->product_id;
                } else if ($leadProduct->type === 'Service') {
                    $productData['service_id'] = $leadProduct->product_id;
                }

                return $productData;
            });

            // Format followups
            $formattedFollowups = $lead->followups->map(function ($followup) {
                return [
                    'id' => $followup->id,
                    'uuid' => $followup->uuid,
                    'next_followup_date' => formatDate($followup->next_followup_date),
                    'notes' => $followup->notes,
                    'challenges' => $followup->challenges,
                    'category' => $followup->leadCategory ? [
                        'id' => $followup->leadCategory->id,
                        'uuid' => $followup->leadCategory->uuid,
                        'title' => $followup->leadCategory->title,
                    ] : null,
                    'created_by' => $followup->createdBy ? [
                        'id' => $followup->createdBy->id,
                        'uuid' => $followup->createdBy->uuid,
                        'name' => $followup->createdBy->name,
                    ] : null,
                    'created_at' => formatDate($followup->created_at),
                ];
            })->sortByDesc('created_at')->values();

            // Get related sales
            $sales = Sales::where('lead_id', $lead->id)
                ->where('company_id', $companyId)
                ->with(['customer.primaryContact:id,uuid,name'])
                ->get()
                ->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'uuid' => $sale->uuid,
                        'sale_date' => formatDate($sale->sale_date),
                        'grand_total' => $sale->grand_total,
                        'paid' => $sale->paid,
                        'status' => $sale->status,
                    ];
                });

            // Get related customers through sales (since customers table doesn't have lead_id column)
            $customerIds = Sales::where('lead_id', $lead->id)
                ->where('company_id', $companyId)
                ->pluck('customer_id')
                ->unique()
                ->toArray();
            
            $customers = Customer::whereIn('id', $customerIds)
                ->where('company_id', $companyId)
                ->get()
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'uuid' => $customer->uuid,
                        'customer_code' => $customer->customer_code,
                    ];
                });

            // Calculate statistics
            $daysInPipeline = $lead->created_at ? now()->diffInDays($lead->created_at) : 0;
            $isOverdue = $lead->next_followup_date && $lead->next_followup_date < now()->toDateString();

            return success_response([
                'uuid' => $lead->uuid,
                'lead_id' => $lead->lead_id,
                'status' => $lead->status,
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
                'primary_contact' => $primaryContact ? [
                    'id' => $primaryContact->id,
                    'uuid' => $primaryContact->uuid,
                    'name' => $primaryContact->name,
                    'phone' => $primaryContact->phone,
                    'email' => $primaryContact->email,
                    'profile_image' => $primaryContact->profile_image,
                    'profile_image_url' => getFileUrl($primaryContact->profile_image),
                ] : null,
                'contacts' => $formattedContacts,
                'products' => $formattedProducts,
                'followups' => $formattedFollowups,
                'sales' => $sales,
                'customers' => $customers,
                'financial' => [
                    'subtotal' => $lead->subtotal,
                    'discount' => $lead->discount,
                    'grand_total' => $lead->grand_total,
                    'negotiated_price' => $lead->negotiated_price,
                ],
                'dates' => [
                    'created_at' => formatDate($lead->created_at),
                    'updated_at' => formatDate($lead->updated_at),
                    'last_contacted_at' => formatDate($lead->last_contacted_at),
                    'next_followup_date' => formatDate($lead->next_followup_date),
                ],
                'notes' => $lead->notes,
                'challenges' => $lead->challenges,
                'statistics' => [
                    'days_in_pipeline' => $daysInPipeline,
                    'followup_count' => $lead->followups->count(),
                    'product_count' => $lead->products->count(),
                    'contact_count' => $lead->leadContacts->count(),
                    'sales_count' => $sales->count(),
                    'total_sales_value' => $sales->sum('grand_total'),
                    'is_overdue' => $isOverdue,
                ],
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
            ]);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
 

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $uuid)
    {
        DB::beginTransaction();
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            $request->validate([
                'lead_category_id' => 'required|exists:lead_categories,id',
                'notes' => 'nullable|string',
                'next_followup_date' => 'required|date',
                'challenges' => 'nullable|array',
                'challenges.*' => 'integer|exists:challenges,id',
            ]);

            $lead = Lead::where('uuid', $uuid)
                ->where('company_id', $companyId)
                ->first();

            if (!$lead) {
                return error_response('Lead not found', 404);
            }
             
            $lead->lead_category_id = $request->lead_category_id;
            $lead->next_followup_date = $request->next_followup_date;
            $lead->notes = $request->notes;
            if ($request->has('challenges')) {
                $lead->challenges = $request->challenges && is_array($request->challenges) ? json_encode($request->challenges) : null;
            }
            $lead->last_contacted_at = now(); 
            $lead->updated_by = $authUser->id;
            $lead->save(); 

            Followup::create([
                'company_id' => $lead->company_id,   
                'lead_id' => $lead->id,
                'lead_categorie_id' => $request->lead_category_id ?? $lead->lead_category_id,
                'next_followup_date' => $request->next_followup_date ?? $lead->next_followup_date,
                'notes' => $request->notes ?? $lead->notes,
                'challenges' => $request->challenges && is_array($request->challenges) ? json_encode($request->challenges) : ($lead->challenges ?? null),
                'created_by' => $authUser->id,
            ]);

            DB::commit();
            return success_response(null, 'Lead updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($uuid)
    {
        DB::beginTransaction();
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            $lead = Lead::where('uuid', $uuid)
                ->where('company_id', $companyId)
                ->first();

            if (!$lead) {
                return error_response('Lead not found', 404);
            }

            // Soft delete the lead
            $lead->deleted_by = $authUser->id;
            $lead->save();
            $lead->delete();

            DB::commit();
            return success_response(null, 'Lead deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }
}
