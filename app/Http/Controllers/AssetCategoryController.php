<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AssetCategoryController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-asset-categories')) {
            $categories = AssetCategory::query()
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-asset-categories')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-asset-categories')) {
                        $q->where('created_by', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('search'), fn($q) => $q->where('name', 'like', '%' . request('search') . '%'))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('FixedAssets/Categories/Index', [
                'categories' => $categories,
                'filters' => request()->only(['search', 'sort', 'direction', 'per_page']),
            ]);
        }

        return back()->with('error', __('Permission denied'));
    }

    public function create()
    {
        if (Auth::user()->can('create-asset-categories')) {
            return Inertia::render('FixedAssets/Categories/Create');
        }

        return back()->with('error', __('Permission denied'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('create-asset-categories')) {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('asset_categories')->where(function ($query) {
                        return $query->where('created_by', creatorId());
                    }),
                ],
                'description' => 'nullable|string',
            ]);

            $category = new AssetCategory();
            $category->name = $validated['name'];
            $category->description = $validated['description'] ?? null;
            $category->created_by = creatorId();
            $category->save();

            return redirect()->route('asset-categories.index')->with('success', __('The asset category has been created successfully.'));
        }

        return redirect()->route('asset-categories.index')->with('error', __('Permission denied'));
    }

    public function edit(AssetCategory $assetCategory)
    {
        if (Auth::user()->can('edit-asset-categories') && $assetCategory->created_by == creatorId()) {
            return Inertia::render('FixedAssets/Categories/Edit', [
                'category' => $assetCategory,
            ]);
        }

        return redirect()->route('asset-categories.index')->with('error', __('Permission denied'));
    }

    public function update(Request $request, AssetCategory $assetCategory)
    {
        if (Auth::user()->can('edit-asset-categories') && $assetCategory->created_by == creatorId()) {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('asset_categories')->ignore($assetCategory->id)->where(function ($query) {
                        return $query->where('created_by', creatorId());
                    }),
                ],
                'description' => 'nullable|string',
            ]);

            $assetCategory->name = $validated['name'];
            $assetCategory->description = $validated['description'] ?? null;
            $assetCategory->save();

            return back()->with('success', __('The asset category has been updated successfully.'));
        }

        return redirect()->route('asset-categories.index')->with('error', __('Permission denied'));
    }

    public function destroy(AssetCategory $assetCategory)
    {
        if (Auth::user()->can('delete-asset-categories') && $assetCategory->created_by == creatorId()) {
            if ($assetCategory->assets()->exists()) {
                return back()->with('error', __('Cannot delete category with existing assets. Remove or reassign the assets first.'));
            }

            $assetCategory->delete();

            return back()->with('success', __('The asset category has been deleted.'));
        }

        return redirect()->route('asset-categories.index')->with('error', __('Permission denied'));
    }
}
