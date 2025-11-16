<?php

namespace App\Http\Controllers\Followup;

use App\Http\Controllers\Controller;
use App\Http\Requests\FollowupStoreRequest;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\LeadProduct;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FollowupController extends Controller
{
    public function index(Request $request){
        try{
            $lead = Lead::where('uuid', $request->uuid)->first();
            if(!$lead){
                return error_response(null,404,"Lead not found");
            }
            $followup = Followup::where('lead_id',$lead->id)->get();
            $data = $followup->map(function($data){
                return  [
                    'customer_name' => $data->user->name??"",
                    'date' => formatDate($data->created_at), 
                    'followup_by' => $data->createdBy->name,
                    'category' => $data->leadCategory->title,
                    'notes' => $data->notes,
                ];
            });
            return success_response($data);
        }catch(Exception $e){
            return error_response($e->getMessage());
        }
    }

    public function store(FollowupStoreRequest $request){
        DB::beginTransaction(); 
        try {
            $lead = Lead::where('uuid', $request->uuid)->first(); 
            if (!$lead) {
                return error_response(null, 404,"Lead not found");
            }
    
            $authUser = Auth::user();
            $lead->update([
                'lead_categorie_id' => $request->lead_categorie_id,
                'purchase_probability' => $request->purchase_probability,
                'price' => $request->price,
                'next_followup_date' => $request->next_followup_date,
                'last_contacted_at' => now(),
                'updated_by' => $authUser->id,
            ]);
    
            $this->createLeadService($lead, $request->product_ids ?? []); 
    
            Followup::create([
                'company_id' => $lead->company_id,
                'user_id' => $lead->user_id,
                'customer_id' => $lead->customer_id,
                'lead_id' => $lead->id,
                'lead_categorie_id' => $request->lead_categorie_id,
                'purchase_probability' => $request->purchase_probability,
                'price' => $request->price,
                'next_followup_date' => $request->next_followup_date,
                'notes' => $request->notes,
                'created_by' => Auth::user()->id
            ]); 
    
            DB::commit(); 
            return success_response(null,"Follow-up created successfully");
        } catch (Exception $e) {
            DB::rollBack(); 
            return error_response($e->getMessage(), 500);
        }
    }
    
    public function createLeadService($lead, $newProductIds)
    {
        try {
            $currentProductIds = LeadProduct::where('lead_id', $lead->id)
                ->pluck('product_id')
                ->toArray();
    
            $newProductIds = $newProductIds ?? [];
            $productIdsToAdd = array_diff($newProductIds, $currentProductIds);
            $productIdsToRemove = array_diff($currentProductIds, $newProductIds); 
            if (!empty($productIdsToRemove)) {
                LeadProduct::where('lead_id', $lead->id)
                    ->whereIn('product_id', $productIdsToRemove)
                    ->delete();
            } 
            foreach ($productIdsToAdd as $product_id) {
                LeadProduct::create([
                    'company_id' => $lead->company_id,
                    'user_id' => $lead->user_id,
                    'customer_id' => $lead->customer_id,
                    'lead_id' => $lead->id,
                    'product_id' => $product_id,
                    'created_by' => Auth::user()->id,
                ]);
            }
    
            return true; 
        } catch (Exception $e) {
            throw new Exception("Failed to update services: " . $e->getMessage());
        }
    }
    
}
