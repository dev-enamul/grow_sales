<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserContact;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileUpdateController extends Controller
{
    public function profile_picture(Request $request){
        $request->validate([
            'uuid' => 'required|uuid',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try{
            $uuid = $request->uuid;
            $authUser = Auth::user();
            $user = User::where('uuid',$uuid)->where('company_id',$authUser->company_id)->first(); 
            if(!$user){
                error_response(null,404,"User not found");
            }
            $user->update([
                'profile_image' => $request->file('profile_image') ? $request->file('profile_image')->store('profile_images', 'public') : null,
            ]); 
            return success_response(null,"Profile picture updated successfully");
        }catch(Exception $e){
            return error_response($e->getMessage(),500); 
        }
    }

    public function bio(Request $request){
       try{
        $request->validate([
            'uuid' => 'required|uuid', 
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'marital_status' => 'nullable|string|max:50',
            'phone' => 'required|string|max:25', 
            'dob' => 'nullable|date', 
            'blood_group' => 'nullable|string|max:3',
            'gender' => 'nullable|string|in:Male,Female,Other',
        ]); 

        $uuid = $request->uuid;
        $authUser = Auth::user();
        $user = User::where('uuid',$uuid)->where('company_id',$authUser->company_id)->first();
        if(!$user){
            error_response(null,404,"User not found");
        }
        $user->update([
                'name'          => $request->name,
                'email'         => $request->email,
                'marital_status' => $request->marital_status,
                'phone'         => $request->phone, 
                'dob'           => $request->dob, 
                'blood_group'   => $request->blood_group, 
                'gender'        => $request->gender, 
                'updated_by'    => $authUser->id,
        ]); 
        return success_response(null,"Bio data updated successfully");
       }catch(Exception $e){
        return error_response($e->getMessage(),500);
       }
    }

    public function address($uuid){

    } 
}
