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
            'contact_id' => 'required|integer|exists:contacts,id',  
            'assigned_to' => 'nullable|integer|exists:users,id',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'challenges' => 'nullable|array',
            'challenges.*' => 'integer|exists:challenges,id',
            'next_followup_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'products' => 'nullable|array',
            'products.*.category' => 'required|in:Property,Service',
            // Product ID fields (either product_id or product_sub_category_id should be present)
            'products.*.product_id' => 'nullable|exists:products,id',
            'products.*.product_sub_category_id' => 'nullable|exists:product_sub_categories,id',
            // Property fields
            'products.*.property_unit_id' => 'nullable|exists:product_units,id',
            'products.*.area_id' => 'nullable|exists:areas,id',
            'products.*.property_id' => 'nullable|exists:product_categories,id',
            'products.*.negotiation_price' => 'nullable|numeric|min:0',
            // Service fields
            'products.*.negotiated_price' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('products') && is_array($this->input('products'))) {
                foreach ($this->input('products') as $key => $product) {
                    $category = $product['category'] ?? null;
                    $productId = $product['product_id'] ?? null;
                    $productSubCategoryId = $product['product_sub_category_id'] ?? null;

                    if ($category === 'Property') {
                        // For Property, property_unit_id is required
                        if (empty($product['property_unit_id'])) {
                            $validator->errors()->add("products.{$key}.property_unit_id", 'The property type field is required when category is Property.');
                        }
                        // product_id is optional for Property (can be null)
                    } elseif ($category === 'Service') {
                        // For Service, product_id is required
                        if (empty($productId)) {
                            $validator->errors()->add("products.{$key}.product_id", 'The product_id field is required when category is Service.');
                        }
                    }
                }
            }
        });
    }
}
