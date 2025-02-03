<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserContact;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserContactController extends Controller
{
    public function contact_list($uuid){
       try{
            $authUser = Auth::user();
            $user = User::where('uuid', $uuid)->where('company_id',$authUser->company_id)->first();
            if(!$user){
               return  error_response(null,404,"User not found");
            }
            $user_contacts = UserContact::where('user_id',$user->id)->get();
            return success_response($user_contacts);
       }catch(Exception $e){
            return error_response($e->getMessage());
       }
    }

    public function add_contact(Request $request)
    { 
        $request->validate([
            'uuid' => 'required|uuid',
            'name' => 'nullable|string|max:255',
            'relationship_or_role' => 'nullable|string|max:255',
            'office_phone' => 'nullable|string|max:20',
            'personal_phone' => 'nullable|string|max:20',
            'office_email' => 'nullable|email|max:45',
            'personal_email' => 'nullable|email|max:45',
            'website' => 'nullable|string',
            'whatsapp' => 'nullable|string|max:20',
            'imo' => 'nullable|string|max:20',
            'facebook' => 'nullable|string|max:100',
            'linkedin' => 'nullable|string|max:100',
        ]); 
        $authUser = Auth::user(); 
        $user = User::where('uuid', $request->uuid)
                    ->where('company_id', $authUser->company_id)
                    ->first();

        if (!$user) { 
            return error_response(null, 404, "User not found");
        } 
        try {
            UserContact::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'relationship_or_role' => $request->relationship_or_role,
                'office_phone' => $request->office_phone,
                'personal_phone' => $request->personal_phone,
                'office_email' => $request->office_email,
                'personal_email' => $request->personal_email,
                'website' => $request->website,
                'whatsapp' => $request->whatsapp,
                'imo' => $request->imo,
                'facebook' => $request->facebook,
                'linkedin' => $request->linkedin,
                'created_by' => $authUser->id,
            ]); 
            return success_response(null, "Contact added successfully"); 
        } catch (Exception $e) { 
            return error_response($e->getMessage(), 500, "An error occurred while adding the contact");
        }
    }

    public function update_contact(Request $request)
    { 
        $request->validate([
            'contact_id'   => 'required',
            'uuid' => 'required|uuid',
            'name' => 'nullable|string|max:255',
            'relationship_or_role' => 'nullable|string|max:255',
            'office_phone' => 'nullable|string|max:20',
            'personal_phone' => 'nullable|string|max:20',
            'office_email' => 'nullable|email|max:45',
            'personal_email' => 'nullable|email|max:45',
            'website' => 'nullable|string',
            'whatsapp' => 'nullable|string|max:20',
            'imo' => 'nullable|string|max:20',
            'facebook' => 'nullable|string|max:100',
            'linkedin' => 'nullable|string|max:100',
        ]);
     
        $authUser = Auth::user(); 
        $user = User::where('uuid', $request->uuid)
                    ->where('company_id', $authUser->company_id)
                    ->first();
    
        if (!$user) { 
            return error_response(null, 404, "User not found");
        }
     
        $userContact = UserContact::where('user_id', $user->id)
                                  ->where('id', $request->contact_id)
                                  ->first();
    
        if (!$userContact) { 
            return error_response(null, 404, "User contact not found");
        }
     
        try {
            $userContact->update([
                'name' => $request->name,
                'relationship_or_role' => $request->relationship_or_role,
                'office_phone' => $request->office_phone,
                'personal_phone' => $request->personal_phone,
                'office_email' => $request->office_email,
                'personal_email' => $request->personal_email,
                'website' => $request->website,
                'whatsapp' => $request->whatsapp,
                'imo' => $request->imo,
                'facebook' => $request->facebook,
                'linkedin' => $request->linkedin,
                'updated_by' => $authUser->id,  
            ]); 
            return success_response(null, "Contact updated successfully"); 
        } catch (Exception $e) { 
            return error_response($e->getMessage(), 500, "An error occurred while updating the contact");
        }
    } 

    public function show_contact(Request $request)
    { 
        $request->validate([
            'contact_id' => 'required|integer', 
            'uuid' => 'required|uuid', 
        ]);
 
        $authUser = Auth::user(); 
        $user = User::where('uuid', $request->uuid)
                    ->where('company_id', $authUser->company_id)
                    ->first();

        if (!$user) { 
            return error_response(null, 404, "User not found");
        } 
        $userContact = UserContact::where('user_id', $user->id)
                                ->where('id', $request->contact_id)
                                ->first();

        if (!$userContact) { 
            return error_response(null, 404, "User contact not found");
        } 
        return success_response($userContact, "Contact retrieved successfully");
    }

    

}
