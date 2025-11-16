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

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $category = $request->category_id;
            $status = $request->status ?? "Active";
            
            $query = Lead::query()
                ->leftJoin('users', 'leads.user_id', '=', 'users.id')
                ->leftJoin('lead_products', 'leads.id', '=', 'lead_products.lead_id')
                ->leftJoin('products', 'lead_products.product_id', '=', 'products.id')
                ->select('leads.uuid as lead_id', 'leads.next_followup_date', 'leads.last_contacted_at','users.uuid as customer_uuid', 
                         'users.name as user_name', 'users.email as user_email', 'users.phone as user_phone', 
                         'products.id as product_id', 'products.name as product_name')
                ->where('leads.status', $status);
            if ($category) {
                $query->where('leads.lead_categorie_id', $category);
            } 
            $datas = $query
                ->orderByRaw('next_followup_date IS NULL DESC, next_followup_date DESC')
                ->get(); 

            $datas = $datas->groupBy('lead_id')->map(function ($leads) {
                $lead = $leads->first(); 
                $products = $lead->products->pluck('name');   
                return [
                    'uuid' => $lead->lead_id,
                    'customer_uuid' => $lead->customer_uuid,
                    'name' => $lead->user_name,
                    'email' => $lead->user_email,
                    'phone' => $lead->user_phone,
                    'next_followup_date' => formatDate($lead->next_followup_date),
                    'last_contacted_at' => formatDate($lead->last_contacted_at),
                    'producs' => $products,
                ];
            })->values()->all();   
            return success_response($datas);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }  
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LeadStoreRequest $request)
    {
        DB::beginTransaction();
        try{ 
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image'); 
                $imagePath = $image->store('profile_images', 'public'); 
                $fullImageUrl = asset('storage/' . $imagePath);  
            } else { 
                $fullImageUrl = null;
            }  
            $authUser = Auth::user();
    
            $user = User::create([ 
                'name' => $request->name,
                'phone' => $request->primary_phone,
                'email' => $request->primary_email, 
                'password' => Hash::make('12345678'),
                'user_type' => 'customer',
                'profile_image' =>  $fullImageUrl,
            ]);
     
            $customer = Customer::create([
                'user_id' => $user->id,
                'lead_source_id' => $request->lead_source_id,
                'referred_by'  => $authUser->id,
            ]);
    
            $lead_category = LeadCategory::where('status',1)->first(); 
            $lead = Lead::create([ 
                'lead_id' => Lead::generateNextLeadId(),
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'lead_categorie_id' => $lead_category->id,
                'priority' => $request->priority,
                'price' => $request->price,
                'last_contacted_at' => now(),
                'next_followup_date' => Carbon::now()->addDay(10),
                'assigned_to' => $request->assigned_to,
                'lead_source_id' => $request->lead_source_id,
            ]); 
    
            Followup::create([ 
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'lead_id' =>  $lead->id,
                'lead_categorie_id' =>$lead_category->id,
                'notes' => $request->notes, 
            ]);
    
            if(isset($request->interested_project) && count($request->interested_project) > 0){
                foreach($request->interested_project as $project){
                    LeadProduct::create([ 
                        'user_id' => $user->id,
                        'customer_id' => $customer->id, 
                        'lead_id' => $lead->id,
                        'product_id' => $project['product_id'], 
                        'area_id' => $project['area_id'], 
                        "product_unit_id" => $project['product_unit_id'],
                        "product_category_id" => $project['product_category_id'],
                        "product_sub_category_id" => $project['product_sub_category_id'],
                        "qty" => $project['qty']
                    ]);
                }
            } 
    
            UserDetail::create([
                'user_id'           => $user->id,
                'customer_id'       => $customer->id,
                'company_id'        => $request->company_id,
                'name'              => $request->name,
                'primary_phone'      => $request->primary_phone,
                'secondary_phone'    => $request->secondary_phone,
                'primary_email'      => $request->primary_email,
                'secondary_email'    => $request->secondary_email,
                'website'           => $request->website,
                'whatsapp'          => $request->whatsapp,
                'imo'               => $request->imo,
                'facebook'          => $request->facebook,
                'linkedin'          => $request->linkedin, 
                "dob" => $request->dob,
                "gender" => $request->gender,
                "marital_status" => $request->marital_status,
                "blood_group" => $request->blood_group,
                "religion" => $request->religion,
                "education" => $request->education,
                "profession" => $request->profession,
                "relationship_or_role" => $request->relationship_or_role,
                "is_decision_maker" => true,
            ]);
    
            if(isset($request->permanent_country) || isset($request->permanent_zip_code) ||  isset($request->permanent_address)){
          
               UserAddress::create([
                    'user_id' => $user->id,
                    'address_type'      => "permanent",
                    'country'           => $request->permanent_country,
                    'division'          => $request->permanent_division,
                    'district'          => $request->permanent_district,
                    'upazila_or_thana'  => $request->permanent_upazila_or_thana,
                    "zip_code"          => $request->permanent_zip_code,
                    'address'           => $request->permanent_address, 
                    "is_same_present_permanent" => $request->is_same_present_permanent,
                    'created_by'    => $authUser->id,
               ]);
    
               if(!$request->is_same_present_permanent){  
                    UserAddress::create([
                            'user_id' => $user->id,
                            'address_type'      => "present",
                            'country'           => $request->present_country,
                            'division'          => $request->present_division,
                            'district'          => $request->present_district,
                            'upazila_or_thana'  => $request->present_upazila_or_thana,
                            "zip_code"          => $request->present_zip_code,
                            'address'           => $request->present_address, 
                            "is_same_present_permanent" => $request->is_same_present_permanent,
                            'created_by'    => $authUser->id,
                    ]); 
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
