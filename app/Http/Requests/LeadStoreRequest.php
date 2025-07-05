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
            'name' => 'required|string|max:100',
            'primary_phone' => 'required|string|max:20|unique:users,phone',
            'primary_email' => 'nullable|email|unique:users,email',
            'secondary_phone' => 'nullable|string|max:20',
            'secondary_email' => 'nullable|email',
            'assigned_to' => 'nullable|exists:users,id',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'priority' => 'nullable|string',
            'price' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,others',
            'marital_status' => 'nullable|in:married,unmarried,divorced',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'religion' => 'nullable|string|max:45',
            'education' => 'nullable|string|max:45',
            'profession' => 'nullable|string|max:45',
            'relationship_or_role' => 'nullable|string|max:100', 
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'interested_project' => 'nullable|array',
            'interested_project.*.product_id' => 'nullable',
            'interested_project.*.product_unit_id' => 'nullable',
            'interested_project.*.area_id' => 'nullable',
            'interested_project.*.product_category_id' => 'nullable',
            'interested_project.*.product_sub_category_id' => 'nullable',
            'interested_project.*.qty' => 'nullable|numeric',

            // Address fields
            'permanent_country' => 'nullable|string',
            'permanent_division' => 'nullable|string',
            'permanent_district' => 'nullable|string',
            'permanent_upazila_or_thana' => 'nullable|string',
            'permanent_zip_code' => 'nullable|string',
            'permanent_address' => 'nullable|string',
            'present_country' => 'nullable|string',
            'present_division' => 'nullable|string',
            'present_district' => 'nullable|string',
            'present_upazila_or_thana' => 'nullable|string',
            'present_zip_code' => 'nullable|string',
            'present_address' => 'nullable|string',
            'is_same_present_permanent' => 'nullable|boolean',
        ];
    }
}
