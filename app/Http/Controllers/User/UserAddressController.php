<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserContact;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAddressController extends Controller
{
    public function show($uuid){
        try { 
            $user = User::where('uuid', $uuid)->first(); 
            if (!$user) {
                return error_response(null, 404, "User not found");
            } 
            $address = UserAddress::where('user_id', $user->id)->get();  
            return success_response($address);
        } catch (Exception $e) { 
            return error_response($e->getMessage(), 500);
        }
    }
    

    public function update(Request $request) {
        $request->validate([
            'uuid' => 'required|uuid',
            'permanent_country' => 'required|string|max:255',
            'permanent_division' => 'required|string|max:255',
            'permanent_district' => 'required|string|max:255',
            'permanent_upazila_or_thana' => 'required|string|max:255',
            'permanent_zip_code' => 'nullable|string|max:20',
            'permanent_address' => 'nullable|string|max:500',
            'is_same_present_permanent' => 'required|boolean',
            'present_country' => 'required_if:is_same_present_permanent,false|string|max:255',
            'present_division' => 'required_if:is_same_present_permanent,false|string|max:255',
            'present_district' => 'required_if:is_same_present_permanent,false|string|max:255',
            'present_upazila_or_thana' => 'required_if:is_same_present_permanent,false|string|max:255',
            'present_zip_code' => 'nullable|string|max:20',
            'present_address' => 'nullable|string|max:500',
        ]);  
    
        try{
            $uuid = $request->uuid;
            $authUser = Auth::user();
            $user = User::where('uuid', $uuid)->where('company_id', $authUser->company_id)->first(); 
            
            if (!$user) {
                return error_response(null, "User not found", 404); 
            }
        
            UserAddress::where('user_id', $user->id)->delete();  
            UserAddress::create([
                'user_id' => $user->id,
                'address_type' => 'permanent',
                'country' => $request->permanent_country,
                'division' => $request->permanent_division,
                'district' => $request->permanent_district,
                'upazila_or_thana' => $request->permanent_upazila_or_thana,
                'zip_code' => $request->permanent_zip_code,
                'address' => $request->permanent_address, 
                'is_same_present_permanent' => $request->is_same_present_permanent,
                'created_by' => $authUser->id,
            ]);
        
            if (!$request->is_same_present_permanent) {
                UserAddress::create([
                    'user_id' => $user->id,
                    'address_type' => 'present',
                    'country' => $request->present_country,
                    'division' => $request->present_division,
                    'district' => $request->present_district,
                    'upazila_or_thana' => $request->present_upazila_or_thana,
                    'zip_code' => $request->present_zip_code,
                    'address' => $request->present_address, 
                    'is_same_present_permanent' => $request->is_same_present_permanent,
                    'created_by' => $authUser->id,
                ]);
            } 
            return success_response(null,"User address updated successfully");
        }catch(Exception $e){
            return error_response($e->getMessage(),500);
        }
    }
    
}
