<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{ 
    public function index(){
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
}
