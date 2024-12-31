<?php

namespace App\Http\Controllers\Auth;
 
use App\Auth\Services\RegisterService; 
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
                    ["uuid" => $unverified->uuid],
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
            'role_id' => 1,
            'company_id' => $companyId,
        ]);
    }

    private function createEmployee($userId)
    {
        Employee::create([
            'user_id' => $userId,
            'employee_id' => Employee::generateNextEmployeeId(),
            'status' => 1, // Active status
        ]);
    }
}
