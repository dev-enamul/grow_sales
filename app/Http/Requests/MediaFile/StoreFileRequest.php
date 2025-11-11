<?php

namespace App\Http\Requests\MediaFile;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
        ];
    }
}

