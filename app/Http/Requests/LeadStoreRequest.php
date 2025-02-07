<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadStoreRequest extends FormRequest
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
            'phone' => 'nullable|string|digits_between:10,15',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:8',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'marital_status' => 'nullable|in:married,unmarried,divorced',
            'dob' => 'nullable|date',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'gender' => 'nullable|in:male,female,others',
    
            // Customer data validation
            'lead_source_id' => 'required|exists:lead_sources,id',
            'referred_by' => 'nullable|exists:users,uuid',
    
            // Lead data validation
            'purchase_probability' => 'nullable|integer|between:0,100',
            'assigned_to' => 'nullable|exists:users,uuid',
    
            // Product selection validation
            'products' => 'nullable|array',
            'products.*' => 'exists:products,id',
    
            // User Contact data validation
            'office_phone' => 'nullable|numeric|digits_between:10,15',
            'personal_phone' => 'nullable|numeric|digits_between:10,15',
            'office_email' => 'nullable|email|max:255',
            'personal_email' => 'nullable|email|max:255',
            'website' => 'nullable|url',
            'whatsapp' => 'nullable|string|max:20',
            'imo' => 'nullable|string|max:20',
            'facebook' => 'nullable|url',
            'linkedin' => 'nullable|url',
    
            // User Address validation
            'permanent_country' => 'nullable|string|max:100',
            'permanent_zip_code' => 'nullable|string|max:20',
            'permanent_address' => 'nullable|string|max:500',
            'is_same_present_permanent' => 'nullable|boolean',
            'present_country' => 'nullable|string|max:100',
            'present_zip_code' => 'nullable|string|max:20',
            'present_address' => 'nullable|string|max:500',
        ];
    }
}
