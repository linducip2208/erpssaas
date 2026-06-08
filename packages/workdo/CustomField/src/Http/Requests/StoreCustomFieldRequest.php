<?php

namespace Workdo\CustomField\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id' => 'required|exists:custom_field_groups,id',
            'name' => 'required|string|max:255',
            'field_type' => 'required|in:text,number,select,date,file,textarea,checkbox,radio',
            'label' => 'required|string|max:255',
            'placeholder' => 'nullable|string|max:255',
            'is_required' => 'boolean',
            'options' => 'nullable|array',
            'default_value' => 'nullable|string|max:255',
            'sort_order' => 'integer',
        ];
    }
}
