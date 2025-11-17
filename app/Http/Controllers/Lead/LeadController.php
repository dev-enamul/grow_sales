<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadStoreRequest;
use App\Models\Customer;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\LeadCategory;
use App\Models\LeadProduct;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserContact;
use App\Models\UserDetail;
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
            $category = $request->category_id;
            $status = $request->status ?? "Active";
            $selectOnly = $request->boolean('select');

            // Base query with relations
            $query = Lead::query()
                ->with([
                    'user:id,uuid,name,email,phone',
                    'products' => function ($q) {
                        $q->select('products.id', 'products.name');
                    }
                ])
                ->where('status', $status)
                ->when($category, function ($q) use ($category) {
                    $q->where('lead_categorie_id', $category);
                });

            // Select only minimal set for dropdowns
            if ($selectOnly) {
                $items = $query
                    ->select('id', 'uuid', 'user_id')
                    ->latest()
                    ->take(10)
                    ->get()
                    ->map(function ($lead) {
                        return [
                            'id' => $lead->id,
                            'name' => optional($lead->user)->name,
                        ];
                    });
                return success_response($items);
            }

            // Sorting: next_followup_date (nulls last), then latest
            $query->orderByRaw('next_followup_date IS NULL DESC, next_followup_date DESC');

            // Paginate
            $paginated = $this->paginateQuery($query, $request);

            // Map response
            $paginated['data'] = collect($paginated['data'])->map(function ($lead) {
                return [
                    'uuid' => $lead->uuid,
                    'customer_uuid' => optional($lead->user)->uuid,
                    'name' => optional($lead->user)->name,
                    'email' => optional($lead->user)->email,
                    'phone' => optional($lead->user)->phone,
                    'next_followup_date' => formatDate($lead->next_followup_date),
                    'last_contacted_at' => formatDate($lead->last_contacted_at),
                    'products' => collect($lead->products)->pluck('name')->values(),
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
        try{  
            $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'gender' => 'nullable|in:male,female,others',
                'profile_image' => 'nullable',
                'lead_source_id' => 'nullable|exists:lead_sources,id',
                'campaign_id' => 'nullable|exists:campaigns,id',
                'lead_categorie_id' => 'nullable|exists:lead_categories,id',
                'priority' => 'nullable|string|max:50',
                'price' => 'nullable|numeric|min:0',
                'next_followup_date' => 'nullable|date',
                'assigned_to' => 'nullable|integer|exists:users,id',
                'relationship_or_role' => 'nullable|string|max:100',
                'is_decision_maker' => 'nullable|boolean',
                'religion' => 'nullable|string|max:45',
                'avalable_time' => 'nullable|date',
                'notes' => 'nullable|string',
            ]);

            if (!$request->phone && !$request->email) {
                return error_response(['phone' => ['Phone or Email is required'], 'email' => ['Phone or Email is required']], 422, 'Validation failed');
            }

            // Profile image can be a file URL or file_id; store as-is for now
            $fullImageUrl = $request->profile_image ?: null;
            $authUser = Auth::user();
    
            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email, 
                'password' => Hash::make('12345678'),
                'user_type' => 'customer',
                'profile_image' =>  $fullImageUrl,
            ]);
     
            $customer = Customer::create([
                'user_id' => $user->id,
                'referred_by'  => $authUser->id,
            ]);
    
            $lead_category = $request->lead_categorie_id 
                ? LeadCategory::where('id', $request->lead_categorie_id)->first()
                : LeadCategory::where('status',1)->first(); 
            $lead = Lead::create([ 
                'lead_id' => Lead::generateNextLeadId(),
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'lead_categorie_id' => optional($lead_category)->id,
                'priority' => $request->priority,
                'price' => $request->price,
                'last_contacted_at' => now(),
                'next_followup_date' => $request->next_followup_date ?: Carbon::now()->addDay(10),
                'assigned_to' => $request->assigned_to,
                'lead_source_id' => $request->lead_source_id,
                'campaign_id' => $request->campaign_id,
                'notes' => $request->notes,
            ]); 
    
            Followup::create([ 
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'lead_id' =>  $lead->id,
                'lead_categorie_id' => optional($lead_category)->id,
                'notes' => $request->notes, 
            ]);

            UserDetail::create([
                'user_id'           => $user->id,
                'customer_id'       => $customer->id,
                'company_id'        => $request->company_id,
                'name'              => $request->name,
                'primary_phone'      => $request->phone,
                'primary_email'      => $request->email,
                "gender" => $request->gender,
                "religion" => $request->religion,
                "relationship_or_role" => $request->relationship_or_role,
                "is_decision_maker" => (bool) ($request->is_decision_maker ?? false),
                "avalable_time" => $request->avalable_time,
            ]);

            DB::commit();
            return success_response(null, "Lead created successfully");
        }catch(Exception $e){ 
            DB::rollBack();
            return error_response($e->getMessage());
        } 

    }  


    public function interested(Request $request)
    {
        $request->validate([
            'lead_uuid' => 'required|uuid',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.area_id' => 'nullable|integer|exists:areas,id',
            'products.*.product_unit_id' => 'nullable|integer|exists:product_units,id',
            'products.*.product_category_id' => 'nullable|integer|exists:product_categories,id',
            'products.*.product_sub_category_id' => 'nullable|integer|exists:product_sub_categories,id',
            'products.*.qty' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $lead = Lead::where('uuid', $request->lead_uuid)->first();
            if (!$lead) {
                return error_response('Lead not found', 404);
            }

            $currentProductIds = LeadProduct::where('lead_id', $lead->id)
                ->pluck('product_id')
                ->toArray();

            $incomingProducts = collect($request->products);
            $incomingProductIds = $incomingProducts->pluck('product_id')->toArray();

            // Remove products not in the incoming list
            $productIdsToRemove = array_diff($currentProductIds, $incomingProductIds);
            if (!empty($productIdsToRemove)) {
                LeadProduct::where('lead_id', $lead->id)
                    ->whereIn('product_id', $productIdsToRemove)
                    ->delete();
            }

            // Upsert incoming products
            foreach ($incomingProducts as $p) {
                LeadProduct::updateOrCreate(
                    ['lead_id' => $lead->id, 'product_id' => $p['product_id']],
                    [
                        'user_id' => $lead->user_id,
                        'customer_id' => $lead->customer_id,
                        'area_id' => $p['area_id'] ?? null,
                        'product_unit_id' => $p['product_unit_id'] ?? null,
                        'product_category_id' => $p['product_category_id'] ?? null,
                        'product_sub_category_id' => $p['product_sub_category_id'] ?? null,
                        'qty' => $p['qty'] ?? 1,
                    ]
                );
            }

            DB::commit();
            return success_response(null, 'Interested products updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
