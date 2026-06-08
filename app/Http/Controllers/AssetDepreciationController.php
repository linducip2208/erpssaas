<?php

namespace App\Http\Controllers;

use App\Models\AssetDepreciation;
use App\Models\FixedAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AssetDepreciationController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-asset-depreciations')) {
            $query = AssetDepreciation::with('asset')
                ->whereHas('asset', function ($q) {
                    $q->where('created_by', creatorId());
                });

            if (request('asset_id')) {
                $query->where('fixed_asset_id', request('asset_id'));
            }

            if (request('date_from')) {
                $query->whereDate('depreciation_date', '>=', request('date_from'));
            }

            if (request('date_to')) {
                $query->whereDate('depreciation_date', '<=', request('date_to'));
            }

            $sortField = request('sort', 'depreciation_date');
            $sortDirection = request('direction', 'desc');
            $allowedSortFields = ['depreciation_date', 'amount', 'book_value_before', 'book_value_after', 'accumulated_depreciation', 'created_at'];

            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'depreciation_date';
            }

            $query->orderBy($sortField, $sortDirection);

            $depreciations = $query->paginate(request('per_page', 10))->withQueryString();

            $assets = FixedAsset::where('created_by', creatorId())->select('id', 'name', 'asset_code')->get();

            return Inertia::render('FixedAssets/Depreciations/Index', [
                'depreciations' => $depreciations,
                'assets' => $assets,
                'filters' => request()->only(['asset_id', 'date_from', 'date_to', 'sort', 'direction', 'per_page']),
            ]);
        }

        return back()->with('error', __('Permission denied'));
    }

    public function show(AssetDepreciation $assetDepreciation)
    {
        if (Auth::user()->can('view-asset-depreciations')) {
            $assetDepreciation->load('asset');

            if ($assetDepreciation->asset->created_by != creatorId()) {
                return redirect()->route('asset-depreciations.index')->with('error', __('Permission denied'));
            }

            return Inertia::render('FixedAssets/Depreciations/View', [
                'depreciation' => $assetDepreciation,
            ]);
        }

        return redirect()->route('asset-depreciations.index')->with('error', __('Permission denied'));
    }
}
