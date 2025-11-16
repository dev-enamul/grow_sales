<?php

namespace App\Http\Controllers\Auth;
 
use App\Auth\Services\RegisterService; 
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    const ERROR_USER_NOT_FOUND = 'No such user found.';
    const ERROR_USER_INACTIVE = 'You are inactive. Please contact with admin';
    const ERROR_USER_RESIGNED = 'You have resigned. Please contact with admin';
    const ERROR_COMPANY_PENDING = 'Verification is pending. Please check your email and confirm.';
    const ERROR_EMAIL_ASSOCIATED = 'Already associated with %s. Please resign first or use another email.';

    public function login(LoginRequest $request)
    {
        $user = AuthService::getActiveEmployee($request->email); 
     
        if ($user) {
            $request->authenticate();
            return AuthService::createResponse(Auth::user());
        }
 
        if (AuthService::isUnverifiedCompany($request->email)) {
            return error_response(self::ERROR_COMPANY_PENDING, 404);
        }
 
        if (AuthService::isInactiveEmployee($request->email)) {
            return error_response(self::ERROR_USER_INACTIVE, 404);
        }

        if (AuthService::isResignedEmployee($request->email)) {
            return error_response(self::ERROR_USER_RESIGNED, 404);
        }

        return error_response(self::ERROR_USER_NOT_FOUND, 404);
    }

    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();
        try { 
            $unverified = AuthService::isUnverifiedCompany($request->user_email);
            if ($unverified) {
                return success_response(
                    [
                        "uuid" => $unverified->uuid,
                        'company_uuid' => $unverified->company->uuid,
                    ],
                    "An account is already associated with this email, but the company verification is pending. Please check your email and confirm."
                );
            }
 
            $existingUser = AuthService::checkExistingActiveEmployee($request->user_email);
            if ($existingUser) {
                return error_response(sprintf(self::ERROR_EMAIL_ASSOCIATED, $existingUser->company->name), 409);
            }

            // Create company, user, and employee
            $company = $this->createCompany($request);
            $user = $this->createUser($request, $company->id);
            $this->createEmployee($user->id);

            DB::commit();

            return success_response(
            [
                "uuid" => $user->uuid,
                'company_uuid' => $user->company->uuid,
            ],
                "Registration successful! Please check your email to confirm your account."
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'You have been successfully logged out.'], 200);
    }

    /**
     * Send forgot password email
     * 
     * @param ForgotPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $user = User::where('email', $request->email)
                ->where('user_type', 'employee')
                ->first();

            if (!$user) {
                // Return success even if user not found (security best practice)
                return success_response(null, 'If the email exists, a password reset link has been sent.');
            }

            // Check if user is active
            if (AuthService::isInactiveEmployee($request->email)) {
                return error_response('Your account is inactive. Please contact administrator.', 403);
            }

            if (AuthService::isResignedEmployee($request->email)) {
                return error_response('You have resigned. Please contact administrator.', 403);
            }

            // Send password reset email using common function
            AuthService::sendPasswordResetEmail($user, true);

            return success_response(null, 'If the email exists, a password reset link has been sent.');
        } catch (\Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Reset password using token from email
     * Used for both: Initial password setup and Forgot password
     * 
     * @param PasswordResetRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(PasswordResetRequest $request)
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->password = Hash::make($password);
                    $user->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return success_response(null, 'Password has been reset successfully. You can now login with your new password.');
            }

            return error_response('Invalid or expired token. Please request a new password reset link.', 400);
        } catch (\Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
  

    private function createCompany($request)
    {
        return Company::create([
            'name' => $request->company_name,
            'website' => $request->website,
            'address' => $request->address,
            'category_id' => $request->category_id,
        ]);
    }

    private function createUser($request, $companyId)
    {
        return User::create([
            'name' => $request->user_name,
            'email' => $request->user_email,
            'phone' => $request->user_phone,
            'password' => Hash::make($request->password),
            'user_type' => 'employee', 
            'company_id' => $companyId,
        ]);
    }

    private function createEmployee($userId)
    {
        Employee::create([
            'user_id' => $userId,
            'employee_id' => Employee::generateNextEmployeeId(),
            'status' => 1, // Active status
            'is_admin' => 1,
        ]);
    }
}
