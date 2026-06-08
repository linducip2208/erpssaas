<?php

namespace Workdo\SideMenuBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'href' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'permission' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:custom_menus,id',
            'role_id' => 'nullable|exists:roles,id',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
