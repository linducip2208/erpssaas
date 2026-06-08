<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequisition;
use App\Models\RequestQuotation;
use App\Models\RfqVendor;
use App\Models\GoodsReceiptNote;
use App\Models\GrnItem;
use App\Models\Warehouse;
use Workdo\Account\Models\Vendor;
use Workdo\ProductService\Models\ProductServiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage-purchase-orders')) {
            return back()->with('error', __('Permission denied'));
        }

        $query = PurchaseOrder::with(['vendor', 'warehouse', 'creator'])
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
        if ($request->search) {
            $query->where('po_number', 'like', '%' . $request->search . '%');
        }
        if ($request->date_range) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) === 2) {
                $query->whereBetween('order_date', [$dates[0], $dates[1]]);
            }
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSortFields = ['po_number', 'order_date', 'expected_delivery_date', 'total_amount', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSortFields) || empty($sortField)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->get('per_page', 10);
        $orders = $query->paginate($perPage);
        $vendors = Vendor::where('created_by', creatorId())
            ->where('is_active', true)
            ->select('id', 'company_name')
            ->get();
        $warehouses = Warehouse::where('is_active', true)
            ->select('id', 'name')
            ->where('created_by', creatorId())
            ->get();

        return Inertia::render('PurchaseOrders/Index', [
            'orders' => $orders,
            'vendors' => $vendors,
            'warehouses' => $warehouses,
            'filters' => $request->only(['vendor_id', 'warehouse_id', 'status', 'search', 'date_range']),
        ]);
    }

    public function create(Request $request)
    {
        if (!Auth::user()->can('create-purchase-orders')) {
            return back()->with('error', __('Permission denied'));
        }

        $vendors = Vendor::where('created_by', creatorId())
            ->where('is_active', true)
            ->select('id', 'company_name', 'contact_person_name')
            ->get();
        $warehouses = Warehouse::where('is_active', true)
            ->select('id', 'name', 'address')
            ->where('created_by', creatorId())
            ->get();
        $products = ProductServiceItem::where('is_active', true)
            ->where('created_by', creatorId())
            ->select('id', 'name', 'sku', 'purchase_price', 'unit')
            ->get();

        $requisitions = PurchaseRequisition::where('created_by', creatorId())
            ->where('status', 'approved')
            ->select('id', 'pr_number', 'reason')
            ->get();

        $selectedPr = null;
        if ($request->purchase_requisition_id) {
            $selectedPr = PurchaseRequisition::with(['items.product'])
                ->where('id', $request->purchase_requisition_id)
                ->where('created_by', creatorId())
                ->first();
        }

        return Inertia::render('PurchaseOrders/Create', [
            'vendors' => $vendors,
            'warehouses' => $warehouses,
            'products' => $products,
            'requisitions' => $requisitions,
            'selectedPr' => $selectedPr,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('create-purchase-orders')) {
            return redirect()->route('purchase-orders.index')->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'order_date' => 'required|date',
            'expected_delivery_date' => 'required|date|after_or_equal:order_date',
            'vendor_id' => 'required|exists:vendors,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'purchase_requisition_id' => 'nullable|exists:purchase_requisitions,id',
            'request_quotation_id' => 'nullable|exists:request_quotations,id',
            'shipping_cost' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'terms_conditions' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1',
            'items.*.product_service_id' => 'required|exists:product_service_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $subtotal = collect($validated['items'])->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $taxAmount = collect($validated['items'])->sum(function ($item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $discount = ($lineTotal * ($item['discount_percentage'] ?? 0)) / 100;
                return (($lineTotal - $discount) * ($item['tax_rate'] ?? 0)) / 100;
            });

            $discountAmount = $validated['discount_amount'] ?? 0;
            $shippingCost = $validated['shipping_cost'] ?? 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount + $shippingCost;

            $po = new PurchaseOrder();
            $po->order_date = $validated['order_date'];
            $po->expected_delivery_date = $validated['expected_delivery_date'];
            $po->vendor_id = $validated['vendor_id'];
            $po->warehouse_id = $validated['warehouse_id'];
            $po->purchase_requisition_id = $validated['purchase_requisition_id'] ?? null;
            $po->request_quotation_id = $validated['request_quotation_id'] ?? null;
            $po->subtotal = $subtotal;
            $po->tax_amount = $taxAmount;
            $po->discount_amount = $discountAmount;
            $po->shipping_cost = $shippingCost;
            $po->total_amount = $totalAmount;
            $po->status = 'draft';
            $po->notes = $validated['notes'] ?? null;
            $po->terms_conditions = $validated['terms_conditions'] ?? null;
            $po->created_by = creatorId();
            $po->save();

            foreach ($validated['items'] as $itemData) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_service_id' => $itemData['product_service_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'tax_rate' => $itemData['tax_rate'] ?? 0,
                    'discount_percentage' => $itemData['discount_percentage'] ?? 0,
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-orders.index')->with('success', __('Purchase order has been created successfully.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function show(PurchaseOrder $order)
    {
        if (!Auth::user()->can('view-purchase-orders') || $order->created_by != creatorId()) {
            return redirect()->route('purchase-orders.index')->with('error', __('Permission denied'));
        }

        $order->load(['vendor', 'warehouse', 'requisition', 'quotation', 'items.product', 'approvedBy', 'creator', 'goodsReceiptNotes']);

        return Inertia::render('PurchaseOrders/Show', [
            'order' => $order,
        ]);
    }

    public function edit(PurchaseOrder $order)
    {
        if (!Auth::user()->can('edit-purchase-orders') || $order->created_by != creatorId()) {
            return redirect()->route('purchase-orders.index')->with('error', __('Permission denied'));
        }

        if ($order->status !== 'draft') {
            return redirect()->route('purchase-orders.index')->with('error', __('Only draft purchase orders can be edited.'));
        }

        $order->load(['items.product']);
        $vendors = Vendor::where('created_by', creatorId())
            ->where('is_active', true)
            ->select('id', 'company_name', 'contact_person_name')
            ->get();
        $warehouses = Warehouse::where('is_active', true)
            ->select('id', 'name', 'address')
            ->where('created_by', creatorId())
            ->get();
        $products = ProductServiceItem::where('is_active', true)
            ->where('created_by', creatorId())
            ->select('id', 'name', 'sku', 'purchase_price', 'unit')
            ->get();

        return Inertia::render('PurchaseOrders/Edit', [
            'order' => $order,
            'vendors' => $vendors,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    public function update(Request $request, PurchaseOrder $order)
    {
        if (!Auth::user()->can('edit-purchase-orders') || $order->created_by != creatorId()) {
            return redirect()->route('purchase-orders.index')->with('error', __('Permission denied'));
        }

        if ($order->status !== 'draft') {
            return redirect()->route('purchase-orders.index')->with('error', __('Only draft purchase orders can be updated.'));
        }

        $validated = $request->validate([
            'order_date' => 'required|date',
            'expected_delivery_date' => 'required|date|after_or_equal:order_date',
            'vendor_id' => 'required|exists:vendors,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'shipping_cost' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'terms_conditions' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1',
            'items.*.product_service_id' => 'required|exists:product_service_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $subtotal = collect($validated['items'])->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $taxAmount = collect($validated['items'])->sum(function ($item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $discount = ($lineTotal * ($item['discount_percentage'] ?? 0)) / 100;
                return (($lineTotal - $discount) * ($item['tax_rate'] ?? 0)) / 100;
            });

            $discountAmount = $validated['discount_amount'] ?? 0;
            $shippingCost = $validated['shipping_cost'] ?? 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount + $shippingCost;

            $order->update([
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'vendor_id' => $validated['vendor_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'terms_conditions' => $validated['terms_conditions'] ?? null,
            ]);

            $order->items()->delete();

            foreach ($validated['items'] as $itemData) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_service_id' => $itemData['product_service_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'tax_rate' => $itemData['tax_rate'] ?? 0,
                    'discount_percentage' => $itemData['discount_percentage'] ?? 0,
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-orders.index')->with('success', __('Purchase order has been updated successfully.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy(PurchaseOrder $order)
    {
        if (!Auth::user()->can('delete-purchase-orders') || $order->created_by != creatorId()) {
            return redirect()->route('purchase-orders.index')->with('error', __('Permission denied'));
        }

        if ($order->status !== 'draft') {
            return back()->with('error', __('Only draft purchase orders can be deleted.'));
        }

        $order->items()->delete();
        $order->delete();

        return redirect()->route('purchase-orders.index')->with('success', __('Purchase order has been deleted.'));
    }

    public function submit(PurchaseOrder $order)
    {
        if (!Auth::user()->can('manage-purchase-orders') || $order->created_by != creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($order->status !== 'draft') {
            return back()->with('error', __('Only draft purchase orders can be submitted.'));
        }

        $order->update(['status' => 'pending_approval']);

        return back()->with('success', __('Purchase order has been submitted for approval.'));
    }

    public function approve(PurchaseOrder $order)
    {
        if (!Auth::user()->can('approve-purchase-orders') || $order->created_by != creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($order->status !== 'pending_approval') {
            return back()->with('error', __('Only pending purchase orders can be approved.'));
        }

        $order->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', __('Purchase order has been approved.'));
    }

    public function reject(Request $request, PurchaseOrder $order)
    {
        if (!Auth::user()->can('approve-purchase-orders') || $order->created_by != creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($order->status !== 'pending_approval') {
            return back()->with('error', __('Only pending purchase orders can be rejected.'));
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:2000',
        ]);

        $order->update([
            'status' => 'rejected',
            'notes' => trim(($order->notes ?? '') . "\nRejected: " . ($validated['rejection_reason'] ?? 'No reason provided')),
        ]);

        return back()->with('success', __('Purchase order has been rejected.'));
    }

    public function receive(PurchaseOrder $order)
    {
        if (!Auth::user()->can('manage-goods-receipt-notes') || $order->created_by != creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        if (!in_array($order->status, ['approved', 'partially_received'])) {
            return back()->with('error', __('Only approved or partially received orders can be received.'));
        }

        $order->load(['items.product', 'vendor', 'warehouse']);

        return Inertia::render('GoodsReceiptNotes/CreateFromPO', [
            'order' => $order,
        ]);
    }

    public function print(PurchaseOrder $order)
    {
        if (!Auth::user()->can('view-purchase-orders') || $order->created_by != creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $order->load(['vendor', 'warehouse', 'items.product', 'requisition']);

        return Inertia::render('PurchaseOrders/Print', [
            'order' => $order,
        ]);
    }

    public function getProducts(Request $request)
    {
        if (!Auth::user()->can('create-purchase-orders') && !Auth::user()->can('edit-purchase-orders')) {
            return response()->json([], 403);
        }

        $products = ProductServiceItem::where('is_active', true)
            ->where('created_by', creatorId())
            ->select('id', 'name', 'sku', 'purchase_price', 'unit', 'type')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'purchase_price' => $product->purchase_price,
                    'unit' => $product->unit,
                    'type' => $product->type,
                ];
            });

        return response()->json($products);
    }
}
