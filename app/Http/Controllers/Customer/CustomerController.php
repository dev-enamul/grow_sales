<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Services\CustomerService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{ 
    public function index(){
        $user = Auth::user();  
        $customers = User::where('company_id', $user->company_id)
        ->where('user_type', 'customer')
        ->whereHas('customer',function($q){
            $q->where('total_sales','>',0);
        })
        ->get()
        ->map(function ($user) {
            return [
                'uuid' => $user->uuid,
                'customer_id' => $user->customer->customer_id ?? null,
                'profile_image' => $user->profile_image,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email, 
                'total_sales' => $user->customer->total_sales??"",
                'referred_by' => $user->customer->referredBy->name??"", 
            ];
        }); 
        return success_response($customers); 
    }

    public function show($uuid){ 
        try {
            $user = User::where('uuid',$uuid)->first(); 
            if (!$user) {
                return error_response('User not found', 404);
            }  

            return success_response([
                "uuid" => $user->uuid,
                "name" => $user->name,  
                'profile_image' => $user->profile_image,
                "phone" => $user->phone,
                'email' => $user->email,
                "marital_status" => $user->marital_status,
                'dob' => $user->dob,
                'blood_group' => $user->blood_group,
                'gender' => $user->gender 
            ]);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

}
