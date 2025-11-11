<?php

namespace App\Http\Requests\MediaFile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'original_name' => ['sometimes', 'required', 'string', 'max:255'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
        ];
    }
}

