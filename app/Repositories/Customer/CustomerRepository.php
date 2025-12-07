<?php 
namespace App\Repositories;

use App\Models\DesignationLog;
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
                'designation' => $user->currentDesignation->designation->title ?? null,
            ];
        }); 
        return $employees;
    }  
    
    public function createDesignationLog($data)
    {
        return DesignationLog::create($data);
    }  
    public function find($id){
        return User::find($id);
    }
}
