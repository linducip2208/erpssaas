<?php

namespace Workdo\CustomField\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomFieldGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'target_module' => 'required|string|max:255',
            'target_type' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }
}
