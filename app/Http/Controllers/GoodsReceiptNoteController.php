<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceiptNote;
use App\Models\GrnItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Warehouse;
use Workdo\Account\Models\Vendor;
use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\ProductService\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GoodsReceiptNoteController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage-goods-receipt-notes')) {
            return back()->with('error', __('Permission denied'));
        }

        $query = GoodsReceiptNote::with(['purchaseOrder', 'vendor', 'warehouse', 'creator'])
            ->where('created_by', creatorId());

        if ($request->vendor_id) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->purchase_order_id) {
            $query->where('purchase_order_id', $request->purchase_order_id);
        }
        if ($request->search) {
            $query->where('grn_number', 'like', '%' . $request->search . '%');
        }
        if ($request->date_range) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) === 2) {
                $query->whereBetween('receipt_date', [$dates[0], $dates[1]]);
            }
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSortFields = ['grn_number', 'receipt_date', 'total_quantity', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSortFields) || empty($sortField)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->get('per_page', 10);
        $grns = $query->paginate($perPage);
        $vendors = Vendor::where('created_by', creatorId())
            ->where('is_active', true)
            ->select('id', 'company_name')
            ->get();
        $warehouses = Warehouse::where('is_active', true)
            ->select('id', 'name')
            ->where('created_by', creatorId())
            ->get();

        return Inertia::render('GoodsReceiptNotes/Index', [
            'grns' => $grns,
            'vendors' => $vendors,
            'warehouses' => $warehouses,
            'filters' => $request->only(['vendor_id', 'warehouse_id', 'status', 'purchase_order_id', 'search', 'date_range']),
        ]);
    }

    public function create(Request $request)
    {
        if (!Auth::user()->can('create-goods-receipt-notes')) {
            return back()->with('error', __('Permission denied'));
        }

        $purchaseOrders = PurchaseOrder::with(['vendor', 'warehouse', 'items.product'])
            ->where('created_by', creatorId())
            ->whereIn('status', ['approved', 'partially_received'])
            ->get();

        $preselectedPo = null;
        if ($request->purchase_order_id) {
            $preselectedPo = PurchaseOrder::with(['items.product'])
                ->where('id', $request->purchase_order_id)
                ->where('created_by', creatorId())
                ->first();
        }

        return Inertia::render('GoodsReceiptNotes/Create', [
            'purchaseOrders' => $purchaseOrders,
            'preselectedPo' => $preselectedPo,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('create-goods-receipt-notes')) {
            return redirect()->route('goods-receipt-notes.index')->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'receipt_date' => 'required|date',
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'inspection_notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.product_service_id' => 'required|exists:product_service_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0.01',
        ]);

        $po = PurchaseOrder::with('items')->findOrFail($validated['purchase_order_id']);
        if ($po->created_by != creatorId()) {
            return redirect()->route('goods-receipt-notes.index')->with('error', __('Permission denied'));
        }

        if (!in_array($po->status, ['approved', 'partially_received'])) {
            return back()->with('error', __('Can only create GRN for approved or partially received orders.'));
        }

        $totalQuantity = 0;
        $poItemUpdates = [];

        foreach ($validated['items'] as $itemData) {
            $poItem = $po->items->firstWhere('id', $itemData['purchase_order_item_id']);
            if (!$poItem) {
                return back()->with('error', __('Invalid purchase order item.'));
            }

            $remaining = $poItem->remainingQuantity();
            if ($itemData['quantity_received'] > $remaining) {
                return back()->with('error', __('Quantity received exceeds remaining for item: :item', ['item' => $poItem->product->name ?? 'Unknown']));
            }

            $totalQuantity += $itemData['quantity_received'];
            $poItemUpdates[$poItem->id] = ($poItemUpdates[$poItem->id] ?? 0) + $itemData['quantity_received'];
        }

        try {
            DB::beginTransaction();

            $grn = new GoodsReceiptNote();
            $grn->receipt_date = $validated['receipt_date'];
            $grn->purchase_order_id = $po->id;
            $grn->vendor_id = $po->vendor_id;
            $grn->warehouse_id = $po->warehouse_id;
            $grn->total_quantity = $totalQuantity;
            $grn->status = 'pending_inspection';
            $grn->inspection_notes = $validated['inspection_notes'] ?? null;
            $grn->created_by = creatorId();
            $grn->save();

            foreach ($validated['items'] as $itemData) {
                $poItem = $po->items->firstWhere('id', $itemData['purchase_order_item_id']);

                GrnItem::create([
                    'goods_receipt_note_id' => $grn->id,
                    'purchase_order_item_id' => $itemData['purchase_order_item_id'],
                    'product_service_id' => $itemData['product_service_id'],
                    'quantity_ordered' => $poItem->quantity,
                    'quantity_received' => $itemData['quantity_received'],
                    'quantity_accepted' => $itemData['quantity_received'],
                    'quantity_rejected' => 0,
                    'unit_price' => $poItem->unit_price,
                    'total_amount' => $itemData['quantity_received'] * $poItem->unit_price,
                    'inspection_result' => 'pending',
                ]);
            }

            foreach ($poItemUpdates as $poItemId => $qty) {
                $poItem = $po->items->firstWhere('id', $poItemId);
                $poItem->update([
                    'received_quantity' => $poItem->received_quantity + $qty,
                ]);
            }

            $this->updatePoStatus($po);

            DB::commit();

            return redirect()->route('goods-receipt-notes.index')->with('success', __('Goods receipt note has been created successfully.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function show(GoodsReceiptNote $grn)
    {
        if (!Auth::user()->can('view-goods-receipt-notes') || $grn->created_by != creatorId()) {
            return redirect()->route('goods-receipt-notes.index')->with('error', __('Permission denied'));
        }

        $grn->load(['purchaseOrder', 'vendor', 'warehouse', 'items.product', 'items.purchaseOrderItem', 'creator']);

        return Inertia::render('GoodsReceiptNotes/Show', [
            'grn' => $grn,
        ]);
    }

    public function destroy(GoodsReceiptNote $grn)
    {
        if (!Auth::user()->can('delete-goods-receipt-notes') || $grn->created_by != creatorId()) {
            return redirect()->route('goods-receipt-notes.index')->with('error', __('Permission denied'));
        }

        if ($grn->status !== 'pending_inspection') {
            return back()->with('error', __('Only pending inspection GRNs can be deleted.'));
        }

        try {
            DB::beginTransaction();

            $po = $grn->purchaseOrder()->with('items')->first();

            foreach ($grn->items as $grnItem) {
                $poItem = $po->items->firstWhere('id', $grnItem->purchase_order_item_id);
                if ($poItem) {
                    $poItem->update([
                        'received_quantity' => max(0, $poItem->received_quantity - $grnItem->quantity_received),
                    ]);
                }
            }

            $grn->items()->delete();
            $grn->delete();

            $this->updatePoStatus($po);

            DB::commit();

            return redirect()->route('goods-receipt-notes.index')->with('success', __('Goods receipt note has been deleted.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function inspect(Request $request, GoodsReceiptNote $grn)
    {
        if (!Auth::user()->can('manage-goods-receipt-notes') || $grn->created_by != creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($grn->status !== 'pending_inspection') {
            return back()->with('error', __('GRN is not pending inspection.'));
        }

        $validated = $request->validate([
            'action' => 'required|in:accept_all,accept_with_rejections',
            'inspection_notes' => 'nullable|string|max:2000',
            'items' => 'required_if:action,accept_with_rejections|array',
            'items.*.id' => 'required_if:action,accept_with_rejections|exists:grn_items,id',
            'items.*.quantity_accepted' => 'required_if:action,accept_with_rejections|numeric|min:0',
            'items.*.quantity_rejected' => 'required_if:action,accept_with_rejections|numeric|min:0',
            'items.*.rejection_reason' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            if ($validated['action'] === 'accept_all') {
                foreach ($grn->items as $item) {
                    $item->update([
                        'quantity_accepted' => $item->quantity_received,
                        'quantity_rejected' => 0,
                        'inspection_result' => 'accepted',
                    ]);

                    $this->updateStock($grn->warehouse_id, $item->product_service_id, $item->quantity_received);
                }
            } else {
                foreach ($validated['items'] as $itemData) {
                    $grnItem = $grn->items->firstWhere('id', $itemData['id']);
                    if (!$grnItem) {
                        continue;
                    }

                    if (($itemData['quantity_accepted'] + $itemData['quantity_rejected']) > $grnItem->quantity_received) {
                        DB::rollBack();
                        return back()->with('error', __('Accepted + rejected quantity exceeds received quantity.'));
                    }

                    $result = $itemData['quantity_rejected'] > 0 ? 'partial' : 'accepted';

                    $grnItem->update([
                        'quantity_accepted' => $itemData['quantity_accepted'],
                        'quantity_rejected' => $itemData['quantity_rejected'],
                        'inspection_result' => $result,
                        'rejection_reason' => $itemData['rejection_reason'] ?? null,
                    ]);

                    if ($itemData['quantity_accepted'] > 0) {
                        $this->updateStock($grn->warehouse_id, $grnItem->product_service_id, $itemData['quantity_accepted']);
                    }
                }
            }

            $grn->update([
                'status' => 'accepted',
                'inspection_notes' => $validated['inspection_notes'] ?? $grn->inspection_notes,
            ]);

            DB::commit();

            return back()->with('success', __('Goods receipt note has been inspected and stock has been updated.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function print(GoodsReceiptNote $grn)
    {
        if (!Auth::user()->can('view-goods-receipt-notes') || $grn->created_by != creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $grn->load(['purchaseOrder', 'vendor', 'warehouse', 'items.product', 'items.purchaseOrderItem']);

        return Inertia::render('GoodsReceiptNotes/Print', [
            'grn' => $grn,
        ]);
    }

    private function updatePoStatus(PurchaseOrder $po): void
    {
        $po->load('items');

        $allFullyReceived = $po->items->every(function ($item) {
            return $item->received_quantity >= $item->quantity;
        });

        if ($allFullyReceived) {
            $po->update(['status' => 'received']);
        } elseif ($po->items->where('received_quantity', '>', 0)->count() > 0) {
            $po->update(['status' => 'partially_received']);
        }
    }

    private function updateStock($warehouseId, $productId, $quantity): void
    {
        $stock = WarehouseStock::firstOrNew([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
        ]);

        $stock->quantity = ($stock->quantity ?? 0) + $quantity;
        $stock->save();
    }
}
