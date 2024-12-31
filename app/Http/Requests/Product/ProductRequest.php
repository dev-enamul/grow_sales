<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
            'regular_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'product_unit_id' => 'nullable',
            'vat_setting_id' => 'nullable',
            'category_id' => 'require',
        ];
    }
}
