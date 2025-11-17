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
            'phone' => 'nullable|string|max:20|unique:users,phone|required_without:email',
            'email' => 'nullable|email|unique:users,email|required_without:phone',
            'gender' => 'nullable|in:male,female,others',
            'profile_image' => 'nullable',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'lead_categorie_id' => 'nullable|exists:lead_categories,id',
            'priority' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'next_followup_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'relationship_or_role' => 'nullable|string|max:100',
            'is_decision_maker' => 'nullable|boolean',
            'religion' => 'nullable|string|max:45',
            'avalable_time' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
