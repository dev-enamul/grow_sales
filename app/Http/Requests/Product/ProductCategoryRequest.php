<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductCategoryRequest extends FormRequest
{
     
     
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
            'slug' => 'required|string|max:255|unique:product_categories,slug,NULL,id,company_id,' . request('company_id'),
            'description' => 'nullable|string',
            'progress_stage' => 'required|in:Ready,Ongoing,Upcomming,Complete',
            'delivery_date' => 'nullable|date',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
 
            'company_id' => 'required|exists:companies,id',
            'category_type_id' => 'nullable|exists:category_types,id',
            'measurment_unit_id' => 'nullable|exists:measurment_units,id',
            'area_id' => 'nullable|exists:areas,id',
 
            'status' => 'nullable|in:0,1',
 
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
            'deleted_by' => 'nullable|exists:users,id',
        ];
    }
}
