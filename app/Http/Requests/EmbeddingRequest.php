<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmbeddingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'input' => ['required', 'array', 'min:1'],
            'input.*' => ['required', 'string'],
        ];
    }
}
