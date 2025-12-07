<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

class ContactUpdateRequest extends FormRequest
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
            'organization_id' => 'nullable|exists:organizations,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'profile_image' => 'nullable|exists:files,id',
            'secondary_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:45',
            'secondary_email' => 'nullable|email|max:45',
            'whatsapp' => 'nullable|string|max:20',
            'facebook' => 'nullable|string|max:100',
            'linkedin' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:Male,Female,Others',
            'marital_status' => 'nullable|in:Married,Unmarried,Divorced',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'religion' => 'nullable|string|max:45',
            'education' => 'nullable|string|max:45',
            'profession' => 'nullable|string|max:45',
            'avalable_time' => 'nullable|date_format:H:i',
            'bio' => 'nullable|string',
            'is_same_present_permanent' => 'nullable|boolean',
            'permanent_address' => 'nullable|array',
            'permanent_address.area_id' => 'nullable|exists:areas,id',
            'permanent_address.postal_code' => 'nullable|string|max:20',
            'permanent_address.address' => 'nullable|string|max:255',
            'permanent_address.latitude' => 'nullable|numeric',
            'permanent_address.longitude' => 'nullable|numeric',
            'present_address' => 'nullable|array',
            'present_address.area_id' => 'nullable|exists:areas,id',
            'present_address.postal_code' => 'nullable|string|max:20',
            'present_address.address' => 'nullable|string|max:255',
            'present_address.latitude' => 'nullable|numeric',
            'present_address.longitude' => 'nullable|numeric',
        ];
    }
}

