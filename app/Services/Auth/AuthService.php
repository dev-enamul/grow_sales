<?php 
namespace App\Services\Auth;

use App\Mail\PasswordSetupMail;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class AuthService {

    /**
     * Send password reset email to user
     * Common function used for both forgot password and initial password setup
     * 
     * @param User $user
     * @param bool $isForgotPassword
     * @return bool Returns true if email sent successfully, false otherwise
     */
    public static function sendPasswordResetEmail(User $user, bool $isForgotPassword = false): bool
    {
        try {
            // Generate password reset token
            $token = Password::createToken($user);
            
            // Get frontend URL from config or env
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
            $resetUrl = "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode($user->email);
            
            // Send email using Mailable
            Mail::to($user->email, $user->name)->send(new PasswordSetupMail($user, $resetUrl, $token, $isForgotPassword));
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public static function createResponse($user)
    {
        $token = $user->createToken('authToken')->plainTextToken;
 
        if ($user->is_admin) {
            $permissions = Permission::pluck('name');
        } else { 
            $permissions = $user?->currentDesignation?->designation?->permissions?->pluck('name') ?? collect();
        }
     
        $data = [
            'token' => $token,
            'user' => [
                'uuid' => $user->uuid,
                'company_uuid' => @$user->company->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => @$user->user_type,
                'designation' => @$user->currentDesignation->designation->name,
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
            ->where('status', 0) // Inactive employee
            ->first();
    }

    public static function isResignedEmployee($email)
    {
        return User::where('email', $email)
            ->where('user_type', 'employee')
            ->where('is_resigned', true) // Resigned employee
            ->first();
    } 


    public static function checkExistingActiveEmployee($email)
    {
        return User::where('email', $email)
            ->where('user_type', 'employee')
            ->where('is_resigned', false) // Active employee
            ->where('status', 1) // Active status
            ->first();
    }

    public static function getActiveEmployee($email)
    {
        return User::where('email', $email)
            ->where('user_type', 'employee')
            ->where('is_resigned', false)
            ->where('status', 1)
            ->whereHas('company', function ($query) {
                $query->where('is_verified', true); 
            })
            ->first();
    }
}