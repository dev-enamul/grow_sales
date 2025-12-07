<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class OrganizationStoreRequest extends FormRequest
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
            'organization_type' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'description' => 'nullable|string',
            'logo' => 'nullable|exists:files,id',
            'founded_date' => 'nullable|date',
            'registration_number' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:255',
            'primary_contact_id' => 'nullable|exists:contacts,id',
        ];
    }
}

