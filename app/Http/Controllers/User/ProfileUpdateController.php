<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ProfilePictureUpdateRequest;
use App\Models\FileItem;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserContact;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileUpdateController extends Controller
{
    public function profile_picture(ProfilePictureUpdateRequest $request)
    {
        try {
            $authUser = Auth::user();
            $user = User::where('uuid', $request->uuid)
                        ->where('company_id', $authUser->company_id)
                        ->first();

            if (!$user) {
                return error_response(null, 404, "User not found");
            }

            $this->updateProfileImage($user, $request->profile_image);

            return success_response([
                'profile_image' => $user->profile_image,
                'profile_image_url' => getFileUrl($user->profile_image),
            ], "Profile picture updated successfully");
    
        } catch (Exception $e) { 
            return error_response($e->getMessage(), 500);
        }
    }

    private function updateProfileImage(User $user, $fileId = null): void
    {
        $user->profile_image = $fileId;
        $user->updated_by = Auth::id();
        $user->save();
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
