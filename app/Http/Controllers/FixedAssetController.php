<?php

namespace App\Http\Controllers;

use App\Models\FixedAsset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciation;
use App\Models\AssetDisposal;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Workdo\Account\Models\Vendor;

class FixedAssetController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-fixed-assets')) {
            $query = FixedAsset::with(['category', 'depreciations'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-fixed-assets')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-fixed-assets')) {
                        $q->where('created_by', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            if (request('category_id')) {
                $query->where('asset_category_id', request('category_id'));
            }
            if (request('status')) {
                $query->where('status', request('status'));
            }
            if (request('location')) {
                $query->where('location', 'like', '%' . request('location') . '%');
            }
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . request('search') . '%')
                      ->orWhere('asset_code', 'like', '%' . request('search') . '%')
                      ->orWhere('serial_number', 'like', '%' . request('search') . '%');
                });
            }

            $sortField = request('sort', 'created_at');
            $sortDirection = request('direction', 'desc');
            $allowedSortFields = ['asset_code', 'name', 'purchase_date', 'purchase_cost', 'current_book_value', 'status', 'created_at'];

            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'created_at';
            }

            $query->orderBy($sortField, $sortDirection);

            $assets = $query->paginate(request('per_page', 10))->withQueryString();
            $categories = AssetCategory::where('created_by', creatorId())->select('id', 'name')->get();

            return Inertia::render('FixedAssets/Index', [
                'assets' => $assets,
                'categories' => $categories,
                'filters' => request()->only(['category_id', 'status', 'location', 'search', 'sort', 'direction', 'per_page']),
            ]);
        }

        return back()->with('error', __('Permission denied'));
    }

    public function create()
    {
        if (Auth::user()->can('create-fixed-assets')) {
            $categories = AssetCategory::where('created_by', creatorId())->select('id', 'name')->get();
            $vendors = Vendor::where('created_by', creatorId())->with('user')->get()->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'name' => $vendor->company_name ?? $vendor->contact_person_name ?? 'Vendor #' . $vendor->id,
                ];
            });

            return Inertia::render('FixedAssets/Create', [
                'categories' => $categories,
                'vendors' => $vendors,
            ]);
        }

        return back()->with('error', __('Permission denied'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('create-fixed-assets')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'asset_category_id' => 'nullable|exists:asset_categories,id',
                'vendor_id' => 'nullable|exists:vendors,id',
                'purchase_date' => 'required|date',
                'purchase_cost' => 'required|numeric|min:0',
                'salvage_value' => 'nullable|numeric|min:0',
                'useful_life_years' => 'required|integer|min:1',
                'depreciation_method' => ['required', Rule::in(['straight_line', 'declining_balance'])],
                'depreciation_rate' => 'nullable|numeric|min:0|max:100',
                'depreciation_start_date' => 'nullable|date',
                'location' => 'nullable|string|max:255',
                'serial_number' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
            ]);

            DB::transaction(function () use ($validated) {
                $asset = new FixedAsset();
                $asset->name = $validated['name'];
                $asset->description = $validated['description'] ?? null;
                $asset->asset_category_id = $validated['asset_category_id'] ?? null;
                $asset->vendor_id = $validated['vendor_id'] ?? null;
                $asset->purchase_date = $validated['purchase_date'];
                $asset->purchase_cost = $validated['purchase_cost'];
                $asset->salvage_value = $validated['salvage_value'] ?? 0;
                $asset->useful_life_years = $validated['useful_life_years'];
                $asset->depreciation_method = $validated['depreciation_method'];
                $asset->depreciation_rate = $validated['depreciation_rate'] ?? null;
                $asset->depreciation_start_date = $validated['depreciation_start_date'] ?? $validated['purchase_date'];
                $asset->current_book_value = $validated['purchase_cost'];
                $asset->status = 'active';
                $asset->location = $validated['location'] ?? null;
                $asset->serial_number = $validated['serial_number'] ?? null;
                $asset->notes = $validated['notes'] ?? null;
                $asset->created_by = creatorId();
                $asset->save();

                AuditService::log('created', $asset, null, $asset->toArray(), __('Fixed asset :name created', ['name' => $asset->name]));
            });

            return redirect()->route('fixed-assets.index')->with('success', __('The fixed asset has been created successfully.'));
        }

        return redirect()->route('fixed-assets.index')->with('error', __('Permission denied'));
    }

    public function show(FixedAsset $fixedAsset)
    {
        if (Auth::user()->can('view-fixed-assets') && $fixedAsset->created_by == creatorId()) {
            $fixedAsset->load(['category', 'depreciations' => fn($q) => $q->latest()]);
            $accumulated = $fixedAsset->depreciations()->sum('amount');

            return Inertia::render('FixedAssets/View', [
                'asset' => $fixedAsset,
                'accumulated_depreciation' => $accumulated,
                'depreciation_schedule' => $fixedAsset->depreciations,
            ]);
        }

        return redirect()->route('fixed-assets.index')->with('error', __('Permission denied'));
    }

    public function edit(FixedAsset $fixedAsset)
    {
        if (Auth::user()->can('edit-fixed-assets') && $fixedAsset->created_by == creatorId()) {
            $categories = AssetCategory::where('created_by', creatorId())->select('id', 'name')->get();
            $vendors = Vendor::where('created_by', creatorId())->with('user')->get()->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'name' => $vendor->company_name ?? $vendor->contact_person_name ?? 'Vendor #' . $vendor->id,
                ];
            });

            return Inertia::render('FixedAssets/Edit', [
                'asset' => $fixedAsset,
                'categories' => $categories,
                'vendors' => $vendors,
            ]);
        }

        return redirect()->route('fixed-assets.index')->with('error', __('Permission denied'));
    }

    public function update(Request $request, FixedAsset $fixedAsset)
    {
        if (Auth::user()->can('edit-fixed-assets') && $fixedAsset->created_by == creatorId()) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'asset_category_id' => 'nullable|exists:asset_categories,id',
                'vendor_id' => 'nullable|exists:vendors,id',
                'purchase_date' => 'required|date',
                'purchase_cost' => 'required|numeric|min:0',
                'salvage_value' => 'nullable|numeric|min:0',
                'useful_life_years' => 'required|integer|min:1',
                'depreciation_method' => ['required', Rule::in(['straight_line', 'declining_balance'])],
                'depreciation_rate' => 'nullable|numeric|min:0|max:100',
                'depreciation_start_date' => 'nullable|date',
                'location' => 'nullable|string|max:255',
                'serial_number' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
            ]);

            $oldValues = $fixedAsset->toArray();

            $fixedAsset->name = $validated['name'];
            $fixedAsset->description = $validated['description'] ?? null;
            $fixedAsset->asset_category_id = $validated['asset_category_id'] ?? null;
            $fixedAsset->vendor_id = $validated['vendor_id'] ?? null;
            $fixedAsset->purchase_date = $validated['purchase_date'];
            $fixedAsset->purchase_cost = $validated['purchase_cost'];
            $fixedAsset->salvage_value = $validated['salvage_value'] ?? 0;
            $fixedAsset->useful_life_years = $validated['useful_life_years'];
            $fixedAsset->depreciation_method = $validated['depreciation_method'];
            $fixedAsset->depreciation_rate = $validated['depreciation_rate'] ?? null;
            $fixedAsset->depreciation_start_date = $validated['depreciation_start_date'] ?? $validated['purchase_date'];
            $fixedAsset->location = $validated['location'] ?? null;
            $fixedAsset->serial_number = $validated['serial_number'] ?? null;
            $fixedAsset->notes = $validated['notes'] ?? null;
            $fixedAsset->save();

            AuditService::log('updated', $fixedAsset, $oldValues, $fixedAsset->toArray(), __('Fixed asset :name updated', ['name' => $fixedAsset->name]));

            return redirect()->route('fixed-assets.index')->with('success', __('The fixed asset has been updated successfully.'));
        }

        return redirect()->route('fixed-assets.index')->with('error', __('Permission denied'));
    }

    public function destroy(FixedAsset $fixedAsset)
    {
        if (Auth::user()->can('delete-fixed-assets') && $fixedAsset->created_by == creatorId()) {
            if ($fixedAsset->depreciations()->exists()) {
                return back()->with('error', __('Cannot delete asset with existing depreciation records. Dispose the asset instead.'));
            }

            $fixedAsset->delete();

            AuditService::log('deleted', $fixedAsset, $fixedAsset->toArray(), null, __('Fixed asset :name deleted', ['name' => $fixedAsset->name]));

            return redirect()->route('fixed-assets.index')->with('success', __('The fixed asset has been deleted.'));
        }

        return redirect()->route('fixed-assets.index')->with('error', __('Permission denied'));
    }

    public function runDepreciation(FixedAsset $fixedAsset)
    {
        if (Auth::user()->can('manage-fixed-assets') && $fixedAsset->created_by == creatorId()) {
            if ($fixedAsset->status !== 'active') {
                return back()->with('error', __('Cannot run depreciation on a non-active asset.'));
            }

            DB::transaction(function () use ($fixedAsset) {
                $monthlyAmount = $fixedAsset->getMonthlyDepreciationAmount();
                $accumulated = $fixedAsset->depreciations()->sum('amount');
                $bookValueBefore = $fixedAsset->current_book_value;
                $bookValueAfter = max($bookValueBefore - $monthlyAmount, $fixedAsset->salvage_value);

                if ($bookValueAfter <= $fixedAsset->salvage_value && $bookValueBefore <= $fixedAsset->salvage_value) {
                    return;
                }

                if ($bookValueAfter < $fixedAsset->salvage_value) {
                    $bookValueAfter = $fixedAsset->salvage_value;
                }

                $actualDepreciation = $bookValueBefore - $bookValueAfter;

                $depreciation = new AssetDepreciation();
                $depreciation->fixed_asset_id = $fixedAsset->id;
                $depreciation->depreciation_date = now();
                $depreciation->amount = $actualDepreciation;
                $depreciation->book_value_before = $bookValueBefore;
                $depreciation->book_value_after = $bookValueAfter;
                $depreciation->accumulated_depreciation = $accumulated + $actualDepreciation;
                $depreciation->notes = __('Monthly depreciation for :method', ['method' => $fixedAsset->depreciation_method]);
                $depreciation->save();

                $fixedAsset->current_book_value = $bookValueAfter;
                $fixedAsset->save();

                AuditService::log('depreciation_ran', $fixedAsset, null, [
                    'depreciation_id' => $depreciation->id,
                    'amount' => $actualDepreciation,
                    'book_value_after' => $bookValueAfter,
                ], __('Depreciation recorded for asset :name', ['name' => $fixedAsset->name]));
            });

            return back()->with('success', __('Depreciation has been recorded successfully.'));
        }

        return back()->with('error', __('Permission denied'));
    }

    public function runAllDepreciation()
    {
        if (Auth::user()->can('manage-fixed-assets')) {
            $activeAssets = FixedAsset::where('created_by', creatorId())
                ->where('status', 'active')
                ->get();

            if ($activeAssets->isEmpty()) {
                return back()->with('info', __('No active assets found for depreciation.'));
            }

            $processed = 0;

            DB::transaction(function () use ($activeAssets, &$processed) {
                foreach ($activeAssets as $asset) {
                    $monthlyAmount = $asset->getMonthlyDepreciationAmount();
                    $accumulated = $asset->depreciations()->sum('amount');
                    $bookValueBefore = $asset->current_book_value;
                    $bookValueAfter = max($bookValueBefore - $monthlyAmount, $asset->salvage_value);

                    if ($bookValueAfter <= $asset->salvage_value && $bookValueBefore <= $asset->salvage_value) {
                        continue;
                    }

                    if ($bookValueAfter < $asset->salvage_value) {
                        $bookValueAfter = $asset->salvage_value;
                    }

                    $actualDepreciation = $bookValueBefore - $bookValueAfter;

                    if ($actualDepreciation <= 0) {
                        continue;
                    }

                    $depreciation = new AssetDepreciation();
                    $depreciation->fixed_asset_id = $asset->id;
                    $depreciation->depreciation_date = now();
                    $depreciation->amount = $actualDepreciation;
                    $depreciation->book_value_before = $bookValueBefore;
                    $depreciation->book_value_after = $bookValueAfter;
                    $depreciation->accumulated_depreciation = $accumulated + $actualDepreciation;
                    $depreciation->notes = __('Monthly depreciation for :method', ['method' => $asset->depreciation_method]);
                    $depreciation->save();

                    $asset->current_book_value = $bookValueAfter;
                    $asset->save();

                    AuditService::log('depreciation_ran', $asset, null, [
                        'depreciation_id' => $depreciation->id,
                        'amount' => $actualDepreciation,
                        'book_value_after' => $bookValueAfter,
                    ], __('Depreciation recorded for asset :name', ['name' => $asset->name]));

                    $processed++;
                }
            });

            return back()->with('success', __('Depreciation has been recorded for :count asset(s).', ['count' => $processed]));
        }

        return back()->with('error', __('Permission denied'));
    }

    public function dispose(Request $request, FixedAsset $fixedAsset)
    {
        if (Auth::user()->can('manage-fixed-assets') && $fixedAsset->created_by == creatorId()) {
            if ($fixedAsset->status === 'disposed') {
                return back()->with('error', __('This asset is already disposed.'));
            }

            $validated = $request->validate([
                'disposal_date' => 'required|date',
                'sale_amount' => 'nullable|numeric|min:0',
                'disposal_method' => ['required', Rule::in(['sold', 'scrapped', 'donated', 'lost'])],
                'notes' => 'nullable|string',
            ]);

            DB::transaction(function () use ($validated, $fixedAsset) {
                $disposal = new AssetDisposal();
                $disposal->fixed_asset_id = $fixedAsset->id;
                $disposal->disposal_date = $validated['disposal_date'];
                $disposal->sale_amount = $validated['sale_amount'] ?? 0;
                $disposal->book_value = $fixedAsset->current_book_value;
                $disposal->gain_loss = ($disposal->sale_amount ?? 0) - $disposal->book_value;
                $disposal->disposal_method = $validated['disposal_method'];
                $disposal->notes = $validated['notes'] ?? null;
                $disposal->created_by = creatorId();
                $disposal->save();

                $fixedAsset->status = 'disposed';
                $fixedAsset->save();

                AuditService::log('disposed', $fixedAsset, null, $disposal->toArray(), __('Fixed asset :name disposed via :method', ['name' => $fixedAsset->name, 'method' => $validated['disposal_method']]));
            });

            return back()->with('success', __('The fixed asset has been disposed successfully.'));
        }

        return back()->with('error', __('Permission denied'));
    }
}
