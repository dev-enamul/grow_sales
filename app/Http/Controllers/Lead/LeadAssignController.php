<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class LeadAssignController extends Controller
{
    public function __invoke(Request $request)
    { 
        $request->validate([
            'lead_uuid' => 'required|exists:leads,uuid',
            'assigned_to' => 'required|exists:users,uuid',
        ]);

        try {  
            $lead = Lead::where('uuid',$request->lead_uuid)->first();
            if(!$lead){
                return error_response(null,404,"Lead not found");
            } 

            $assigned_user = User::where('uuid',$request->assigned_to)->first();
            if(!$assigned_user){
                return error_response(null,404,"User not found");
            } 

            $lead->assigned_to = $assigned_user->id;
            $lead->save();  
            return success_response(null, "Lead has been successfully assigned to the selected user.");
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500, "An error occurred while assigning the lead. Please try again later.");
        } 
    }

}
