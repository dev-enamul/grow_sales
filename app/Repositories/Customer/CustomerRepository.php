<?php 
namespace App\Repositories;

use App\Models\Employee;
use App\Models\EmployeeDesignation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerRepository
{
    public function getAllCustomer()
    {
        $user = Auth::user(); 
        $employees = User::where('company_id', $user->company_id)
        ->where('user_type', 'customer')
        ->whereHas('customer',function($q){
            $q->whereNotNull('customer_id');
        })
        ->get()
        ->map(function ($user) {
            return [ 
                'uuid' => $user->uuid,
                'customer_id' => $user->customer->customer_id ?? null,
                'profile_image' => $user->profile_image,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email, 
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
