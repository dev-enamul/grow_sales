<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest; 

class EmployeeStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */ 
 
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [ 
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:15',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'role_id' => 'required|exists:roles,id',
            'dob' => 'nullable|date',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'gender' => 'nullable|in:male,female,others',
            'password' => 'nullable|string|min:8',
            
            // Contact-related fields
            'office_phone' => 'nullable|string|max:15',
            'personal_phone' => 'nullable|string|max:15',
            'office_email' => 'nullable|email|max:45',
            'personal_email' => 'nullable|email|max:45',
            'whatsapp' => 'nullable|string|max:20',
            'imo' => 'nullable|string|max:20',
            'facebook' => 'nullable|string|max:100',
            'linkedin' => 'nullable|string|max:100',

            // Address-related fields
            'permanent_country' => 'nullable|string|max:255',
            'permanent_division' => 'nullable|string|max:255',
            'permanent_district' => 'nullable|string|max:255',
            'permanent_upazila_or_thana' => 'nullable|string|max:255',
            'permanent_zip_code' => 'nullable|string|max:10',
            'permanent_address' => 'nullable|string|max:500',
            'is_same_present_permanent' => 'nullable|boolean',

            // Present address (only if different from permanent)
            'present_country' => 'nullable|string|max:255',
            'present_division' => 'nullable|string|max:255',
            'present_district' => 'nullable|string|max:255',
            'present_upazila_or_thana' => 'nullable|string|max:255',
            'present_zip_code' => 'nullable|string|max:10',
            'present_address' => 'nullable|string|max:500',

            // Employee-related fields
            'designation_id' => 'required|exists:designations,id',
            'referred_by' => 'nullable|exists:users,id',
            'reporting_user_id' => 'nullable|exists:users,id',
        ];
    }
}
