<?php
 namespace App\Http\Requests;

 use Illuminate\Contracts\Validation\Validator;
 use Illuminate\Foundation\Http\FormRequest;
 use Illuminate\Http\Exceptions\HttpResponseException;
 
 class RegisterRequest extends FormRequest
 {
     /**
      * Handle a failed validation attempt.
      */
     protected function failedValidation(Validator $validator)
     {
         throw new HttpResponseException(
             response()->json([
                 'message' => 'Validation failed',
                 'errors' => $validator->errors()
             ], 422)
         );
     }
 
     public function authorize(): bool
     {
         return true;
     }
 
     public function rules(): array
     {
         return [
             'company_name' => 'required|string|max:255',
             'website' => 'nullable|url|max:255',
             'address' => 'nullable|string|max:500',  
             'category_id' => 'required|exists:company_categories,id',
             
             // User Validation
             'user_name' => 'required|string|max:255',
             'user_email' => 'required|email|unique:users,email',
             'user_phone' => 'required|string|max:15|unique:users,phone',
             'password' => 'required|string|min:8',  
         ];
     }
 }
 