<?php 
namespace App\Repositories;

use App\Models\Employee;
use App\Models\EmployeeDesignation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeRepository
{
    public function getAllEmployees()
    {
        $user = Auth::user(); 
        $employees = User::with('employee.designationOnDate')
        ->where('company_id', $user->company_id)
        ->where('user_type', 'employee')
        ->get()
        ->map(function ($user) {
            return [ 
                'uuid' => $user->uuid,
                'employee_id' => $user->employee->employee_id ?? null,
                'profile_image' => $user->profile_image,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'senior_user' => $user->senior_user,
                'junior_user' => $user->junior_user,
                'designation' => $user->employee->currentDesignation->designation->title ?? null,
            ];
        });

        return $employees;
    } 
    public function createEmployee($data)
    {
        return Employee::create($data);
    } 
    public function createEmployeeDesignation($data)
    {
        return EmployeeDesignation::create($data);
    }  
    public function find($id){
        return User::find($id);
    }
}
