<?php

namespace App\Http\Controllers\Visitor;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
 
    protected $userRepo;

    public function __construct(UserRepository $userRepo)
    { 
        $this->userRepo = $userRepo;
    }

    public function store(Request $request){ 
        $user = $this->userRepo->createUser([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone, 
            'user_type' => 'customer',
            'profile_image' => $request->file('profile_image') ? $request->file('profile_image')->store('profile_images', 'public') : null,
            'company_id' => auth()->company_id,
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'lead_source_id' => $request->lead_source_id,
            'visitor_id' => Customer::generateNextVisitorId(),
            'customer_type' => $request->customer_type,
            'ref_id' => $request->ref_id,
            'created_by' => auth()->id,
        ]);

        $this->userRepo->createUserContact([
            'user_id' => $user->id,
            'name' => $request->name ?? $request->user_name,
            'office_phone' => $request->office_phone,
            'personal_phone' => $request->personal_phone,
            'office_email' => $request->office_email,
            'personal_email' => $request->personal_email,
            'emergency_contact_number' => $request->emergency_contact_number,
            'emergency_contact_person' => $request->emergency_contact_person,
        ]);

        $this->userRepo->createUserAddress([
            'user_id' => $user->id,
            'country_id' => $request->country_id,
            'division_id' => $request->division_id,
            'district_id' => $request->district_id,
            'upazila_id' => $request->upazila_id,
            'address' => $request->address,
        ]);
    }
}
