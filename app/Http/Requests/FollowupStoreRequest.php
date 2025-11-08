<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FollowupStoreRequest extends FormRequest
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
            'uuid'  => 'required',
            'lead_categorie_id' => 'required|exists:lead_categories,id',  
            'purchase_probability' => 'nullable|integer|between:0,100', 
            'price' => 'nullable|numeric|min:0', 
            'next_followup_date' => 'nullable|date|after_or_equal:today',  
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
