<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserContact;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileUpdateController extends Controller
{
    public function profile_picture(Request $request)
    {
        $request->validate([
            'uuid' => 'required|uuid',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
    
        try {
            $uuid = $request->uuid;
            $authUser = Auth::user(); 
            $user = User::where('uuid', $uuid)
                        ->where('company_id', $authUser->company_id)
                        ->first();   

            if (!$user) {
                return error_response(null, 404, "User not found");
            }
     
            if ($request->hasFile('image')) {
                $image = $request->file('image'); 
                $imagePath = $image->store('profile_images', 'public'); 
                $fullImageUrl = asset('storage/' . $imagePath); 
                $user->update([
                    'profile_image' => $fullImageUrl,
                ]);
            } else { 
                $user->update([
                    'profile_image' => null,
                ]);
            } 
            return success_response($fullImageUrl, "Profile picture updated successfully");
    
        } catch (Exception $e) { 
            return error_response($e->getMessage(), 500);
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
 
}
