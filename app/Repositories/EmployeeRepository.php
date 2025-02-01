<?php 
namespace App\Repositories;

use App\Models\DesignationLog;
use App\Models\Employee; 
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
                $seniorUserName = null;
                if (!empty($user->senior_user)) { 
                    $firstSeniorUser = User::find($user->senior_user[0]);
                    $seniorUserName = $firstSeniorUser ? $firstSeniorUser->name : null; 
                }

                return [ 
                    'uuid' => $user->uuid,
                    'employee_id' => $user->employee->employee_id ?? null,
                    'profile_image' => $user->profile_image,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'senior_user' => $seniorUserName, 
                    'designation' => $user->employee->currentDesignation->designation->title ?? null,
                ];
            }); 
            
        return $employees;
    }

    public function createEmployee($data)
    {
        return Employee::create($data);
    } 
    public function createDesignationLog($data)
    {
        return DesignationLog::create($data);
    }  
 
}
