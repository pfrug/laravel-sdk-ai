<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RerankRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'query' => ['required', 'string'],
            'documents' => ['required', 'array', 'min:2'],
            'documents.*' => ['required', 'string'],
        ];
    }
}
