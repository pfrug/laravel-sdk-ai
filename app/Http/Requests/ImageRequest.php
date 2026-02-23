<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string'],
            'format' => ['sometimes', Rule::in(['square', 'portrait', 'landscape'])],
            'quality' => ['sometimes', Rule::in(['high', 'medium', 'low'])],
        ];
    }
}
