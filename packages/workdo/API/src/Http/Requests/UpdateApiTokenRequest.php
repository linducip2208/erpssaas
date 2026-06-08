<?php

namespace Workdo\API\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ];
    }
}
