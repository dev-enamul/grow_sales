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
use App\Models\VatSetting;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Traits\PaginatorTrait;
use App\Services\LeadProductService;

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
                'lead_id' => Lead::generateNextLeadId($companyId),
                'organization_id' => $contact->organization_id ?? null,
                'lead_category_id' => $lead_category?->id ?? null,
                'last_contacted_at' => now(),
                'next_followup_date' => $request->next_followup_date ?: Carbon::now()->addDay(7),
                'assigned_to' => $request->assigned_to?? $authUser->id,
                'lead_source_id' => $request->lead_source_id?? null,
                'campaign_id' => $request->campaign_id?? null,
                'affiliate_id' => $request->affiliate_id,
                'challenges' => $request->challenges && is_array($request->challenges) ? json_encode($request->challenges) : null,
                'notes' => $request->notes?? null,
                'other_price' => $request->other_price ?? 0,
                'discount' => $request->discount ?? 0,
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

            Followup::create([
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'lead_category_id' => $lead_category?->id ?? null,
                'next_followup_date' => $request->next_followup_date ?: Carbon::now()->addDay(7),
                'notes' => $request->notes?? "Data created",
                'challenges' => $request->challenges && is_array($request->challenges) ? json_encode($request->challenges) : null,
                'created_by' => $authUser->id,
            ]); 

            // Save lead products
            if ($request->products && is_array($request->products)) {
                foreach ($request->products as $productData) {
                    $category = $productData['category'] ?? null;
                    $productId = $productData['product_id'] ?? null;
                    $productSubCategoryId = $productData['product_sub_category_id'] ?? null;
                    $negotiatedPrice = $productData['negotiated_price'] ?? $productData['negotiation_price'] ?? null;
                    
                    if (empty($productId) && !empty($productSubCategoryId)) {
                        $product = Product::where('sub_category_id', $productSubCategoryId)
                            ->where('company_id', $companyId)
                            ->where('applies_to', $category === 'Service' ? 'service' : 'property')
                            ->first();
                        
                        if ($product) {
                            $productId = $product->id;
                        } 
                    }
 
                    if ($category === 'Service' && empty($productId)) {
                        DB::rollBack();
                        return error_response('Product ID is required for Service category.', 400);
                    }
 
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

                    // Prepare data for LeadProduct
                    $leadProductData = [
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
                        'negotiated_price' => $negotiatedPrice,
                        'notes' => $productData['notes'] ?? null,
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
    public function show($identifier)
    {
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            // Check if identifier is numeric (id) or UUID string
            $query = Lead::where('company_id', $companyId);
            
            if (is_numeric($identifier)) {
                // If numeric, search by id
                $query->where('id', $identifier);
            } else {
                // Otherwise, search by uuid
                $query->where('uuid', $identifier);
            }

            $lead = $query->with([
                    'leadCategory:id,uuid,title',
                    'leadSource:id,uuid,name',
                    'campaign:id,uuid,name',
                    'organization:id,uuid,name,organization_type,industry,website,address',
                    'assignedTo:id,uuid,name,email,phone,profile_image',
                    'affiliate:id,uuid,name,email,phone,profile_image',
                    'createdBy:id,uuid,name',
                    'updatedBy:id,uuid,name',
                    'leadContacts.contact:id,uuid,name,phone,email,profile_image',
                    'products.product:id,uuid,name,price,image,sell_price',
                    'products.product.category:id,uuid,name',
                    'products.product.subCategory:id,uuid,name',
                    'products.productSubCategory:id,uuid,name,sell_price',
                    'products.propertyUnit:id,name',
                    'products.area:id,name',
                    'products.propertyCategory:id,name',
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
                // Ensure contact exists before accessing properties
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
            })->filter()->values();

            // Format products - Load relationships explicitly to avoid cache issues
            $formattedProducts = $lead->products->map(function ($leadProduct) {
                // Load relationships if not already loaded
                if (!$leadProduct->relationLoaded('product')) {
                    $leadProduct->load('product:id,uuid,name,price,image,sell_price');
                }
                if (!$leadProduct->relationLoaded('productSubCategory')) {
                    $leadProduct->load('productSubCategory:id,uuid,name,sell_price');
                }
                
                $product = $leadProduct->product;
                $productSubCategory = $leadProduct->productSubCategory;
                
                // Get base sell_price based on priority:
                // Priority 1: If product_id exists, get from products table
                // Priority 2: If product_id is null, check product_sub_category_id and get from product_sub_categories table
                // Priority 3: If neither found, return 0
                $baseSellPrice = 0;
                
                // Priority 1: Check product_id first
                if ($leadProduct->product_id && $product && isset($product->sell_price)) {
                    // Get from products table
                    $baseSellPrice = $product->sell_price !== null && $product->sell_price !== '' 
                        ? (float) $product->sell_price 
                        : 0;
                } 
                // Priority 2: Only if product_id is null/empty, check product_sub_category_id
                elseif (!$leadProduct->product_id && $leadProduct->product_sub_category_id && $productSubCategory && isset($productSubCategory->sell_price)) {
                    // Get from product_sub_categories table
                    $baseSellPrice = $productSubCategory->sell_price !== null && $productSubCategory->sell_price !== '' 
                        ? (float) $productSubCategory->sell_price 
                        : 0;
                }
                // Priority 3: Default to 0 (already set above)
                
                // Calculate final sell price: (basePrice * quantity) + otherPrice - discount
                $quantity = (float) ($leadProduct->quantity ?? 1);
                $otherPrice = (float) ($leadProduct->other_price ?? 0);
                $discount = (float) ($leadProduct->discount ?? 0);
                $finalSellPrice = ($baseSellPrice * $quantity) + $otherPrice - $discount;
                
                // Get negotiated_price from lead_products table
                $negotiatedPrice = $leadProduct->negotiated_price !== null && $leadProduct->negotiated_price !== '' 
                    ? (float) $leadProduct->negotiated_price 
                    : null;
                
                $productData = [
                    'id' => $leadProduct->id,
                    'uuid' => $leadProduct->uuid,
                    'category' => $leadProduct->type,
                    'sell_price' => $baseSellPrice, // Base price for reference
                    'final_sell_price' => $finalSellPrice, // Final calculated sell price
                    'quantity' => $leadProduct->quantity,
                    'discount' => $leadProduct->discount ?? null,
                    'notes' => $leadProduct->notes,
                    'other_price' => $leadProduct->other_price ?? null,
                    'negotiated_price' => $negotiatedPrice,
                    'negotiation_price' => $negotiatedPrice, // Backward compatibility
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
                } elseif ($productSubCategory) {
                    // If no product but has subcategory, show subcategory name
                    $productData['product'] = [
                        'id' => $productSubCategory->id,
                        'uuid' => $productSubCategory->uuid,
                        'name' => $productSubCategory->name,
                        'price' => $productSubCategory->price ?? null,
                        'image' => $productSubCategory->image ?? null,
                        'image_url' => $productSubCategory->image ? getFileUrl($productSubCategory->image) : null,
                        'category' => null,
                        'sub_category' => null,
                    ];
                }

                if ($leadProduct->type === 'Property') {
                    $productData['property_unit_id'] = $leadProduct->property_unit_id;
                    $productData['property_unit_name'] = $leadProduct->propertyUnit->name ?? null;
                    $productData['area_id'] = $leadProduct->area_id;
                    $productData['area_name'] = $leadProduct->area->name ?? null;
                    $productData['property_id'] = $leadProduct->product_category_id;
                    $productData['property_name'] = $leadProduct->propertyCategory->name ?? null;
                    $productData['layout_id'] = $leadProduct->product_sub_category_id;
                    $productData['layout_name'] = $productSubCategory->name ?? null;
                    $productData['unit_id'] = $leadProduct->product_id;
                    $productData['unit_name'] = $product->name ?? null;
                } else if ($leadProduct->type === 'Service') {
                    $productData['service_id'] = $leadProduct->product_id;
                    $productData['service_name'] = $product->name ?? null;
                }

                return $productData;
            });

            // Format followups
            $formattedFollowups = $lead->followups->map(function ($followup) use ($lead) {
                // Parse challenges if it's a string, otherwise use as is
                $challenges = $followup->challenges;
                if (is_string($challenges)) {
                    $decoded = json_decode($challenges, true);
                    $challenges = $decoded !== null ? $decoded : [];
                } elseif (!is_array($challenges)) {
                    $challenges = [];
                }
                
                // Fetch challenge details if challenge IDs exist
                $challengeDetails = [];
                if (!empty($challenges) && is_array($challenges)) {
                    $challengeModels = \App\Models\Challenge::whereIn('id', $challenges)
                        ->where('company_id', $lead->company_id)
                        ->select('id', 'uuid', 'title')
                        ->get();
                    
                    $challengeDetails = $challengeModels->map(function ($challenge) {
                        return [
                            'id' => $challenge->id,
                            'uuid' => $challenge->uuid,
                            'title' => $challenge->title,
                        ];
                    })->toArray();
                }
                
                return [
                    'id' => $followup->id,
                    'uuid' => $followup->uuid,
                    'next_followup_date' => formatDate($followup->next_followup_date),
                    'notes' => $followup->notes,
                    'challenges' => $challengeDetails,
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
                'affiliate' => $lead->affiliate ? [
                    'id' => $lead->affiliate->id,
                    'uuid' => $lead->affiliate->uuid,
                    'name' => $lead->affiliate->name,
                    'email' => $lead->affiliate->email,
                    'phone' => $lead->affiliate->phone,
                    'profile_image' => $lead->affiliate->profile_image,
                    'profile_image_url' => getFileUrl($lead->affiliate->profile_image),
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
                'other_price' => $lead->other_price ?? 0,
                'discount' => $lead->discount ?? 0,
                'financial' => [
                    'other_price' => $lead->other_price ?? 0,
                    'discount' => $lead->discount ?? 0,
                    'negotiated_price' => $lead->negotiated_price ?? null,
                ],
                'dates' => [
                    'created_at' => formatDate($lead->created_at),
                    'updated_at' => formatDate($lead->updated_at),
                    'last_contacted_at' => formatDate($lead->last_contacted_at),
                    'next_followup_date' => formatDate($lead->next_followup_date),
                ],
                'notes' => $lead->notes,
                'challenges' => (function() use ($lead) {
                    // Parse challenges if it's a string, otherwise use as is
                    $challenges = $lead->challenges;
                    if (is_string($challenges)) {
                        $decoded = json_decode($challenges, true);
                        $challenges = $decoded !== null ? $decoded : [];
                    } elseif (!is_array($challenges)) {
                        $challenges = [];
                    }
                    
                    // Fetch challenge details if challenge IDs exist
                    $challengeDetails = [];
                    if (!empty($challenges) && is_array($challenges)) {
                        $challengeModels = \App\Models\Challenge::whereIn('id', $challenges)
                            ->where('company_id', $lead->company_id)
                            ->select('id', 'uuid', 'title')
                            ->get();
                        
                        $challengeDetails = $challengeModels->map(function ($challenge) {
                            return [
                                'id' => $challenge->id,
                                'uuid' => $challenge->uuid,
                                'title' => $challenge->title,
                            ];
                        })->toArray();
                    }
                    
                    return $challengeDetails;
                })(),
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
                'lead_category_id' => 'sometimes|exists:lead_categories,id',
                'notes' => 'nullable|string',
                'next_followup_date' => 'sometimes|date',
                'challenges' => 'nullable|array',
                'challenges.*' => 'integer|exists:challenges,id',
                'lead_source_id' => 'sometimes|exists:lead_sources,id',
            ]);

            $lead = Lead::where('uuid', $uuid)
                ->where('company_id', $companyId)
                ->first();

            if (!$lead) {
                return error_response('Lead not found', 404);
            }
             
            if ($request->has('lead_category_id')) {
                $lead->lead_category_id = $request->lead_category_id;
            }
            if ($request->has('next_followup_date')) {
                $lead->next_followup_date = $request->next_followup_date;
            }
            if ($request->has('lead_source_id')) {
                $lead->lead_source_id = $request->lead_source_id;
            }
            if ($request->has('notes')) {
                $lead->notes = $request->notes;
            }
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
     * Update decision maker for a lead
     */
    public function updateDecisionMaker(Request $request, $uuid)
    {
        DB::beginTransaction();
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            $request->validate([
                'contact_id' => 'required|integer|exists:contacts,id',
            ]);

            $lead = Lead::where('uuid', $uuid)
                ->where('company_id', $companyId)
                ->first();

            if (!$lead) {
                return error_response('Lead not found', 404);
            }

            // Check if this contact is already associated with this lead
            $leadContact = LeadContact::where('lead_id', $lead->id)
                ->where('contact_id', $request->contact_id)
                ->where('company_id', $companyId)
                ->first();

            if (!$leadContact) {
                return error_response('Contact is not associated with this lead', 404);
            }

            // Set all contacts for this lead to not be decision maker
            LeadContact::where('lead_id', $lead->id)
                ->where('company_id', $companyId)
                ->update(['is_decision_maker' => false, 'updated_by' => $authUser->id]);

            // Set the selected contact as decision maker
            $leadContact->is_decision_maker = true;
            $leadContact->updated_by = $authUser->id;
            $leadContact->save();

            DB::commit();
            return success_response(null, 'Decision maker updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Update lead products
     */
    /**
     * Update lead products
     */
    public function updateProducts(Request $request, $uuid, LeadProductService $leadProductService)
    {
        DB::beginTransaction();
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            $request->validate([
                'products' => 'required|array',
                'products.*.category' => 'required|in:Property,Service',
                'products.*.lead_product_id' => 'nullable|exists:lead_products,id',
                'products.*.product_id' => 'nullable|exists:products,id',
                'products.*.product_sub_category_id' => 'nullable|exists:product_sub_categories,id',
                'products.*.property_unit_id' => 'nullable|exists:product_units,id',
                'products.*.area_id' => 'nullable|exists:areas,id',
                'products.*.property_id' => 'nullable|exists:product_categories,id',
                'products.*.quantity' => 'nullable|numeric|min:1',
                'products.*.other_price' => 'nullable|numeric|min:0',
                'products.*.discount' => 'nullable|numeric|min:0',
                'products.*.negotiation_price' => 'nullable|numeric|min:0',
                'products.*.negotiated_price' => 'nullable|numeric|min:0',
                'products.*.notes' => 'nullable|string',
            ]);

            $lead = Lead::where('uuid', $uuid)
                ->where('company_id', $companyId)
                ->first();

            if (!$lead) {
                return error_response('Lead not found', 404);
            }

            // Sync products using service
            $leadProductService->syncLeadProducts($lead, $request->products, $companyId, $authUser->id, true);

            // Recalculate lead totals
            $leadProducts = LeadProduct::where('lead_id', $lead->id)
                ->where('company_id', $companyId)
                ->get();
            
            // Update discount and other_price from request if provided
            if ($request->has('discount')) {
                $lead->discount = $request->discount;
            } else {
                $lead->discount = $leadProducts->sum('discount');
            }
            
            if ($request->has('other_price')) {
                $lead->other_price = $request->other_price;
            }
            
            $lead->updated_by = $authUser->id;
            $lead->save();

            DB::commit();
            return success_response(null, 'Products updated successfully');
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

    /**
     * Update assigned to for a lead
     */
    public function updateAssignedTo(Request $request, $uuid)
    {
        DB::beginTransaction();
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $lead = Lead::where('uuid', $uuid)
                ->where('company_id', $companyId)
                ->first();

            if (!$lead) {
                return error_response('Lead not found', 404);
            }

            $lead->assigned_to = $request->user_id;
            $lead->updated_by = $authUser->id;
            $lead->save();

            DB::commit();
            return success_response(null, 'Assigned to updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Update affiliate for a lead
     */
    public function updateAffiliate(Request $request, $uuid)
    {
        DB::beginTransaction();
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            $request->validate([
                'affiliate_id' => 'required|integer|exists:users,id',
            ]);

            $lead = Lead::where('uuid', $uuid)
                ->where('company_id', $companyId)
                ->first();

            if (!$lead) {
                return error_response('Lead not found', 404);
            }

            $lead->affiliate_id = $request->affiliate_id;
            $lead->updated_by = $authUser->id;
            $lead->save();

            DB::commit();
            return success_response(null, 'Affiliate updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }
}
