<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VectorSearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'query' => ['required', 'string'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
