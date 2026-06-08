<?php

namespace Workdo\CustomField\Http\Controllers;

use Workdo\CustomField\Models\CustomFieldGroup;
use Workdo\CustomField\Models\CustomField;
use Workdo\CustomField\Http\Requests\StoreCustomFieldGroupRequest;
use Workdo\CustomField\Http\Requests\UpdateCustomFieldGroupRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\CustomField\Events\CreateCustomFieldGroup;
use Workdo\CustomField\Events\UpdateCustomFieldGroup;
use Workdo\CustomField\Events\DestroyCustomFieldGroup;

class CustomFieldGroupController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-custom-field-groups')) {
            $groups = CustomFieldGroup::query()
                ->with(['fields'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-custom-field-groups')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-custom-field-groups')) {
                        $q->where('created_by', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), function ($q) {
                    $q->where('name', 'like', '%' . request('name') . '%');
                })
                ->when(request('target_module'), fn($q) => $q->where('target_module', request('target_module')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('CustomField/CustomField/Index', [
                'groups' => $groups,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreCustomFieldGroupRequest $request)
    {
        if (Auth::user()->can('create-custom-field-groups')) {
            $validated = $request->validated();

            $group = new CustomFieldGroup();
            $group->name = $validated['name'];
            $group->target_module = $validated['target_module'];
            $group->target_type = $validated['target_type'] ?? null;
            $group->is_active = $validated['is_active'] ?? true;
            $group->created_by = creatorId();
            $group->save();

            CreateCustomFieldGroup::dispatch($request, $group);

            return redirect()->route('custom-field.groups.index')->with('success', __('The custom field group has been created successfully.'));
        } else {
            return redirect()->route('custom-field.groups.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateCustomFieldGroupRequest $request, CustomFieldGroup $group)
    {
        if (Auth::user()->can('edit-custom-field-groups')) {
            $validated = $request->validated();

            $group->name = $validated['name'];
            $group->target_module = $validated['target_module'];
            $group->target_type = $validated['target_type'] ?? $group->target_type;
            $group->is_active = $validated['is_active'] ?? $group->is_active;
            $group->save();

            UpdateCustomFieldGroup::dispatch($request, $group);

            return back()->with('success', __('The custom field group details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(CustomFieldGroup $group)
    {
        if (Auth::user()->can('delete-custom-field-groups')) {
            DestroyCustomFieldGroup::dispatch($group);

            $group->delete();

            return redirect()->back()->with('success', __('The custom field group has been deleted.'));
        } else {
            return redirect()->route('custom-field.groups.index')->with('error', __('Permission denied'));
        }
    }
}
