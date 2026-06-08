<?php

namespace Workdo\SideMenuBuilder\Http\Controllers;

use Workdo\SideMenuBuilder\Models\CustomMenu;
use Workdo\SideMenuBuilder\Http\Requests\StoreCustomMenuRequest;
use Workdo\SideMenuBuilder\Http\Requests\UpdateCustomMenuRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomMenuController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-custom-menus')) {
            $menus = CustomMenu::query()
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-custom-menus')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-custom-menus')) {
                        $q->where('created_by', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('title'), function ($q) {
                    $q->where('title', 'like', '%' . request('title') . '%');
                })
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->orderBy('sort_order'))
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $allMenus = CustomMenu::where('created_by', creatorId())->orderBy('sort_order')->get();

            return Inertia::render('SideMenuBuilder/SideMenuBuilder/Index', [
                'menus' => $menus,
                'allMenus' => $allMenus,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreCustomMenuRequest $request)
    {
        if (Auth::user()->can('create-custom-menus')) {
            $validated = $request->validated();

            $menu = new CustomMenu();
            $menu->title = $validated['title'];
            $menu->href = $validated['href'] ?? null;
            $menu->icon = $validated['icon'] ?? null;
            $menu->permission = $validated['permission'] ?? null;
            $menu->parent_id = $validated['parent_id'] ?? null;
            $menu->role_id = $validated['role_id'] ?? null;
            $menu->sort_order = $validated['sort_order'] ?? 0;
            $menu->is_active = $validated['is_active'] ?? true;
            $menu->created_by = creatorId();
            $menu->save();

            return redirect()->route('side-menu-builder.menus.index')->with('success', __('The custom menu has been created successfully.'));
        } else {
            return redirect()->route('side-menu-builder.menus.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateCustomMenuRequest $request, CustomMenu $menu)
    {
        if (Auth::user()->can('edit-custom-menus')) {
            $validated = $request->validated();

            $menu->title = $validated['title'];
            $menu->href = $validated['href'] ?? $menu->href;
            $menu->icon = $validated['icon'] ?? $menu->icon;
            $menu->permission = $validated['permission'] ?? $menu->permission;
            $menu->parent_id = $validated['parent_id'] ?? $menu->parent_id;
            $menu->role_id = $validated['role_id'] ?? $menu->role_id;
            $menu->sort_order = $validated['sort_order'] ?? $menu->sort_order;
            $menu->is_active = $validated['is_active'] ?? $menu->is_active;
            $menu->save();

            return back()->with('success', __('The custom menu details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(CustomMenu $menu)
    {
        if (Auth::user()->can('delete-custom-menus')) {
            $menu->delete();

            return redirect()->back()->with('success', __('The custom menu has been deleted.'));
        } else {
            return redirect()->route('side-menu-builder.menus.index')->with('error', __('Permission denied'));
        }
    }

    public function reorder(Request $request)
    {
        if (Auth::user()->can('reorder-custom-menus')) {
            $items = $request->input('items', []);

            foreach ($items as $index => $item) {
                CustomMenu::where('id', $item['id'])
                    ->where('created_by', creatorId())
                    ->update([
                        'sort_order' => $index,
                        'parent_id' => $item['parent_id'] ?? null,
                    ]);
            }

            return back()->with('success', __('The custom menus have been reordered successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}
