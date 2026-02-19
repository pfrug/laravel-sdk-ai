<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Laravel\Ai\Enums\Lab;

class PromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string'],
            'instructions' => ['sometimes', 'string'],
            'provider' => ['sometimes', Rule::enum(Lab::class)],
            'model' => ['sometimes', 'string'],
        ];
    }

    public function provider(): ?Lab
    {
        return $this->has('provider') ? Lab::from($this->validated('provider')) : null;
    }
}
