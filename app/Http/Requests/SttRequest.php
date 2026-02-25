<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SttRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'audio' => ['required', 'file', 'mimetypes:audio/mpeg,audio/wav,audio/mp4,audio/webm,audio/ogg'],
        ];
    }
}
