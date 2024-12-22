<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\LoginService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDesignation;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {   
        $request->authenticate(); 
        $user = Auth::user();  
        return LoginService::createResponse($user);
    } 

    public function register(RegisterRequest $request){
        DB::beginTransaction();
        try {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
            }

            $profilePicPath = null;
            if ($request->hasFile('profile_image')) {
                $profilePicPath = $request->file('profile_image')->store('profile_images', 'public');
            }  
            
            // Create Company
            $company = Company::create([
                'name' => $request->company_name,
                'website' => $request->website,
                'address' => $request->address, 
                'category_id' => $request->category_id, 
            ]); 

            // Create User
            $user = User::create([
                'name' => $request->user_name,
                'email' => $request->user_email,
                'phone' => $request->user_phone,
                'password' => Hash::make($request->password),
                'user_type' => 'employee',   
                'role_id' => 1,
                'company_id' =>  $company->id,
            ]);   

            // Create Employee record
            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_id' => Employee::generateNextEmployeeId(),
                'status' => 1,
            ]);   
            DB::commit();  
            return success_response(["uuid" => $user->uuid], "Please check your email and confirm."); 
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
}
