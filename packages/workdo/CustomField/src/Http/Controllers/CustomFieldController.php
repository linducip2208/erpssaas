<?php

namespace Workdo\CustomField\Http\Controllers;

use Workdo\CustomField\Models\CustomField;
use Workdo\CustomField\Models\CustomFieldGroup;
use Workdo\CustomField\Http\Requests\StoreCustomFieldRequest;
use Workdo\CustomField\Http\Requests\UpdateCustomFieldRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\CustomField\Events\CreateCustomField;
use Workdo\CustomField\Events\UpdateCustomField;
use Workdo\CustomField\Events\DestroyCustomField;

class CustomFieldController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-custom-field-definitions')) {
            $fields = CustomField::query()
                ->with(['group'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-custom-field-definitions')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-custom-field-definitions')) {
                        $q->where('created_by', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), function ($q) {
                    $q->where('name', 'like', '%' . request('name') . '%');
                })
                ->when(request('group_id'), fn($q) => $q->where('group_id', request('group_id')))
                ->when(request('field_type'), fn($q) => $q->where('field_type', request('field_type')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->orderBy('sort_order')->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $groups = CustomFieldGroup::where('created_by', creatorId())->where('is_active', true)->get();

            return Inertia::render('CustomField/CustomField/Index', [
                'fields' => $fields,
                'groups' => $groups,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreCustomFieldRequest $request)
    {
        if (Auth::user()->can('create-custom-field-definitions')) {
            $validated = $request->validated();

            $field = new CustomField();
            $field->group_id = $validated['group_id'];
            $field->name = $validated['name'];
            $field->field_type = $validated['field_type'];
            $field->label = $validated['label'];
            $field->placeholder = $validated['placeholder'] ?? null;
            $field->is_required = $validated['is_required'] ?? false;
            $field->options = $validated['options'] ?? null;
            $field->default_value = $validated['default_value'] ?? null;
            $field->sort_order = $validated['sort_order'] ?? 0;
            $field->created_by = creatorId();
            $field->save();

            CreateCustomField::dispatch($request, $field);

            return redirect()->route('custom-field.fields.index')->with('success', __('The custom field has been created successfully.'));
        } else {
            return redirect()->route('custom-field.fields.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateCustomFieldRequest $request, CustomField $field)
    {
        if (Auth::user()->can('edit-custom-field-definitions')) {
            $validated = $request->validated();

            $field->group_id = $validated['group_id'];
            $field->name = $validated['name'];
            $field->field_type = $validated['field_type'];
            $field->label = $validated['label'];
            $field->placeholder = $validated['placeholder'] ?? $field->placeholder;
            $field->is_required = $validated['is_required'] ?? $field->is_required;
            $field->options = $validated['options'] ?? $field->options;
            $field->default_value = $validated['default_value'] ?? $field->default_value;
            $field->sort_order = $validated['sort_order'] ?? $field->sort_order;
            $field->save();

            UpdateCustomField::dispatch($request, $field);

            return back()->with('success', __('The custom field details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(CustomField $field)
    {
        if (Auth::user()->can('delete-custom-field-definitions')) {
            DestroyCustomField::dispatch($field);

            $field->delete();

            return redirect()->back()->with('success', __('The custom field has been deleted.'));
        } else {
            return redirect()->route('custom-field.fields.index')->with('error', __('Permission denied'));
        }
    }
}
