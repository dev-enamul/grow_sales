<?php 
namespace App\Services\Auth;

use App\Models\Permission;
use App\Models\User;

class AuthService {

    public static function createResponse($user)
    {  
        $token = $user->createToken('authToken')->plainTextToken;
 
        $permissions = $user->role->slug === 'admin'
            ? Permission::pluck('slug')  
            : $user->role->permissions->pluck('slug'); 
     
        $data = [
            'token' => $token,
            'user' => [
                'uuid' => $user->uuid,
                'company_uuid' => $user->company->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'role' => $user->role->slug,
            ],
            'permissions' => $permissions,
        ];   
        return success_response($data, 'User authenticated successfully.'); 
    }
    
    public static function isUnverifiedCompany($email)
    {
        return User::where('email', $email)
            ->where('user_type', 'employee')
            ->whereHas('company', function ($query) {
                $query->where('is_verified', false);  
            })
            ->first();
    }

    public static function isInactiveEmployee($email)
    {
        return User::where('email', $email)
            ->where('user_type', 'employee')
            ->whereHas('employee', function ($query) {
                $query->where('status', 0); // Inactive employee
            })
            ->first();
    }

    public static function isResignedEmployee($email)
    {
        return User::where('email', $email)
            ->where('user_type', 'employee')
            ->whereHas('employee', function ($query) {
                $query->where('is_resigned', true); // Resigned employee
            })
            ->first();
    } 


    public static function checkExistingActiveEmployee($email)
    {
        return User::where('email', $email)
            ->where('user_type', 'employee')
            ->whereHas('employee', function ($query) {
                $query->where('is_resigned', false); // Active employee
            })
            ->first();
    }

    public static function getActiveEmployee($email)
    {
        return User::where('email', $email)
            ->where('user_type', 'employee')
            ->whereHas('employee', function ($query) {
                $query->where('is_resigned', false)
                    ->where('status', 1); // Active status
            })
            ->whereHas('company', function ($query) {
                $query->where('is_verified', true); // Verified company
            })
            ->first();
    }
}