<?php

namespace App\Http\Controllers;

use App\Models\AssetDisposal;
use App\Models\FixedAsset;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AssetDisposalController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-asset-disposals')) {
            $query = AssetDisposal::with('asset')
                ->whereHas('asset', function ($q) {
                    $q->where('created_by', creatorId());
                });

            if (request('asset_id')) {
                $query->where('fixed_asset_id', request('asset_id'));
            }

            if (request('disposal_method')) {
                $query->where('disposal_method', request('disposal_method'));
            }

            if (request('date_from')) {
                $query->whereDate('disposal_date', '>=', request('date_from'));
            }

            if (request('date_to')) {
                $query->whereDate('disposal_date', '<=', request('date_to'));
            }

            $sortField = request('sort', 'disposal_date');
            $sortDirection = request('direction', 'desc');
            $allowedSortFields = ['disposal_date', 'sale_amount', 'book_value', 'gain_loss', 'disposal_method', 'created_at'];

            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'disposal_date';
            }

            $query->orderBy($sortField, $sortDirection);

            $disposals = $query->paginate(request('per_page', 10))->withQueryString();

            return Inertia::render('FixedAssets/Disposals/Index', [
                'disposals' => $disposals,
                'filters' => request()->only(['asset_id', 'disposal_method', 'date_from', 'date_to', 'sort', 'direction', 'per_page']),
            ]);
        }

        return back()->with('error', __('Permission denied'));
    }

    public function create()
    {
        if (Auth::user()->can('manage-asset-disposals')) {
            $assets = FixedAsset::where('created_by', creatorId())
                ->where('status', 'active')
                ->select('id', 'name', 'asset_code', 'current_book_value', 'location')
                ->get();

            return Inertia::render('FixedAssets/Disposals/Create', [
                'assets' => $assets,
            ]);
        }

        return back()->with('error', __('Permission denied'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('manage-asset-disposals')) {
            $validated = $request->validate([
                'fixed_asset_id' => 'required|exists:fixed_assets,id',
                'disposal_date' => 'required|date',
                'sale_amount' => 'nullable|numeric|min:0',
                'disposal_method' => ['required', Rule::in(['sold', 'scrapped', 'donated', 'lost'])],
                'notes' => 'nullable|string',
            ]);

            $asset = FixedAsset::findOrFail($validated['fixed_asset_id']);

            if ($asset->created_by != creatorId()) {
                return redirect()->route('asset-disposals.index')->with('error', __('Permission denied'));
            }

            if ($asset->status === 'disposed') {
                return back()->with('error', __('This asset is already disposed.'));
            }

            DB::transaction(function () use ($validated, $asset) {
                $disposal = new AssetDisposal();
                $disposal->fixed_asset_id = $asset->id;
                $disposal->disposal_date = $validated['disposal_date'];
                $disposal->sale_amount = $validated['sale_amount'] ?? 0;
                $disposal->book_value = $asset->current_book_value;
                $disposal->gain_loss = ($disposal->sale_amount ?? 0) - $disposal->book_value;
                $disposal->disposal_method = $validated['disposal_method'];
                $disposal->notes = $validated['notes'] ?? null;
                $disposal->created_by = creatorId();
                $disposal->save();

                $asset->status = 'disposed';
                $asset->save();

                AuditService::log('disposed', $asset, null, $disposal->toArray(), __('Fixed asset :name disposed via :method', ['name' => $asset->name, 'method' => $validated['disposal_method']]));
            });

            return redirect()->route('asset-disposals.index')->with('success', __('The fixed asset has been disposed successfully.'));
        }

        return redirect()->route('asset-disposals.index')->with('error', __('Permission denied'));
    }

    public function show(AssetDisposal $assetDisposal)
    {
        if (Auth::user()->can('view-asset-disposals')) {
            $assetDisposal->load('asset');

            if ($assetDisposal->asset->created_by != creatorId()) {
                return redirect()->route('asset-disposals.index')->with('error', __('Permission denied'));
            }

            return Inertia::render('FixedAssets/Disposals/View', [
                'disposal' => $assetDisposal,
            ]);
        }

        return redirect()->route('asset-disposals.index')->with('error', __('Permission denied'));
    }
}
