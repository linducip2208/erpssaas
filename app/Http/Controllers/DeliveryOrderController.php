<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use Workdo\Account\Models\Customer;
use Workdo\ProductService\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DeliveryOrderController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-delivery-orders')) {
            $query = DeliveryOrder::with(['salesOrder', 'customer', 'warehouse'])
                ->where('created_by', creatorId());

            if ($request->customer_id) {
                $query->where('customer_id', $request->customer_id);
            }
            if ($request->warehouse_id) {
                $query->where('warehouse_id', $request->warehouse_id);
            }
            if ($request->status) {
                $query->where('status', $request->status);
            }
            if ($request->search) {
                $query->where('do_number', 'like', '%' . $request->search . '%');
            }
            if ($request->date_range) {
                $dates = explode(' - ', $request->date_range);
                if (count($dates) === 2) {
                    $query->whereBetween('delivery_date', [$dates[0], $dates[1]]);
                }
            }
            if ($request->sales_order_id) {
                $query->where('sales_order_id', $request->sales_order_id);
            }

            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');

            $allowedSortFields = ['do_number', 'delivery_date', 'total_quantity', 'status', 'created_at'];
            if (!in_array($sortField, $allowedSortFields) || empty($sortField)) {
                $sortField = 'created_at';
            }

            $query->orderBy($sortField, $sortDirection);

            $perPage = $request->get('per_page', 10);
            $orders = $query->paginate($perPage);
            $customers = Customer::where('created_by', creatorId())->select('id', 'company_name', 'contact_person_name')->get();
            $warehouses = Warehouse::where('is_active', true)->select('id', 'name')->where('created_by', creatorId())->get();

            return Inertia::render('DeliveryOrders/Index', [
                'orders' => $orders,
                'customers' => $customers,
                'warehouses' => $warehouses,
                'filters' => $request->only(['customer_id', 'warehouse_id', 'status', 'search', 'date_range', 'sales_order_id']),
            ]);
        }

        return back()->with('error', __('Permission denied'));
    }

    public function create(Request $request)
    {
        if (!Auth::user()->can('create-delivery-orders')) {
            return back()->with('error', __('Permission denied'));
        }

        $salesOrders = SalesOrder::with(['customer', 'items.product'])
            ->where('created_by', creatorId())
            ->whereIn('status', ['confirmed', 'partially_delivered'])
            ->whereHas('items', function ($q) {
                $q->whereRaw('delivered_quantity < quantity');
            })
            ->get();

        $warehouses = Warehouse::where('is_active', true)->select('id', 'name', 'address')->where('created_by', creatorId())->get();

        $preselectedOrder = null;
        if ($request->sales_order_id) {
            $preselectedOrder = SalesOrder::with(['items.product'])
                ->where('id', $request->sales_order_id)
                ->where('created_by', creatorId())
                ->first();
        }

        return Inertia::render('DeliveryOrders/Create', [
            'salesOrders' => $salesOrders,
            'warehouses' => $warehouses,
            'preselectedOrder' => $preselectedOrder,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('create-delivery-orders')) {
            return redirect()->route('delivery-orders.index')->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'delivery_date' => 'required|date',
            'sales_order_id' => 'required|exists:sales_orders,id',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.sales_order_item_id' => 'required|exists:sales_order_items,id',
            'items.*.product_service_id' => 'required|exists:product_service_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $salesOrder = SalesOrder::with('items')->findOrFail($validated['sales_order_id']);

        if (!in_array($salesOrder->status, ['confirmed', 'partially_delivered'])) {
            return back()->with('error', __('Can only create delivery for confirmed or partially delivered orders.'));
        }

        $totalQuantity = 0;
        $soItemUpdates = [];

        foreach ($validated['items'] as $itemData) {
            $soItem = $salesOrder->items->firstWhere('id', $itemData['sales_order_item_id']);
            if (!$soItem) {
                return back()->with('error', __('Invalid sales order item.'));
            }

            $remaining = $soItem->remainingQuantity();
            if ($itemData['quantity'] > $remaining) {
                return back()->with('error', __('Quantity exceeds remaining for item: :item', ['item' => $soItem->product->name ?? 'Unknown']));
            }

            $totalQuantity += $itemData['quantity'];
            $soItemUpdates[$soItem->id] = ($soItemUpdates[$soItem->id] ?? 0) + $itemData['quantity'];
        }

        try {
            DB::beginTransaction();

            $deliveryOrder = new DeliveryOrder();
            $deliveryOrder->delivery_date = $validated['delivery_date'];
            $deliveryOrder->sales_order_id = $salesOrder->id;
            $deliveryOrder->customer_id = $salesOrder->customer_id;
            $deliveryOrder->warehouse_id = $salesOrder->warehouse_id;
            $deliveryOrder->total_quantity = $totalQuantity;
            $deliveryOrder->status = 'draft';
            $deliveryOrder->notes = $validated['notes'] ?? null;
            $deliveryOrder->created_by = creatorId();
            $deliveryOrder->save();

            foreach ($validated['items'] as $itemData) {
                DeliveryOrderItem::create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'sales_order_item_id' => $itemData['sales_order_item_id'],
                    'product_service_id' => $itemData['product_service_id'],
                    'quantity' => $itemData['quantity'],
                ]);
            }

            foreach ($soItemUpdates as $soItemId => $qty) {
                $soItem = $salesOrder->items->firstWhere('id', $soItemId);
                $soItem->update([
                    'delivered_quantity' => $soItem->delivered_quantity + $qty,
                ]);
            }

            $this->updateSalesOrderStatus($salesOrder);

            DB::commit();

            return redirect()->route('delivery-orders.index')->with('success', __('Delivery order has been created successfully.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function show(DeliveryOrder $deliveryOrder)
    {
        if (!Auth::user()->can('view-delivery-orders') || $deliveryOrder->created_by != creatorId()) {
            return redirect()->route('delivery-orders.index')->with('error', __('Permission denied'));
        }

        $deliveryOrder->load([
            'salesOrder.items.product',
            'customer',
            'warehouse',
            'items.salesOrderItem.product',
            'items.product',
            'creator',
        ]);

        return Inertia::render('DeliveryOrders/Show', [
            'order' => $deliveryOrder,
        ]);
    }

    public function ship(Request $request, DeliveryOrder $deliveryOrder)
    {
        if (!Auth::user()->can('manage-delivery-orders')) {
            return back()->with('error', __('Permission denied'));
        }

        if ($deliveryOrder->status !== 'draft') {
            return back()->with('error', __('Only draft delivery orders can be shipped.'));
        }

        $validated = $request->validate([
            'carrier' => 'nullable|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
        ]);

        $deliveryOrder->update([
            'status' => 'shipped',
            'carrier' => $validated['carrier'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
        ]);

        return back()->with('success', __('Delivery order has been marked as shipped.'));
    }

    public function deliver(Request $request, DeliveryOrder $deliveryOrder)
    {
        if (!Auth::user()->can('manage-delivery-orders')) {
            return back()->with('error', __('Permission denied'));
        }

        if (!in_array($deliveryOrder->status, ['draft', 'shipped'])) {
            return back()->with('error', __('Only draft or shipped delivery orders can be marked as delivered.'));
        }

        try {
            DB::beginTransaction();

            $deliveryOrder->load(['items', 'salesOrder.items']);

            foreach ($deliveryOrder->items as $doItem) {
                $stock = WarehouseStock::firstOrNew([
                    'warehouse_id' => $deliveryOrder->warehouse_id,
                    'product_id' => $doItem->product_service_id,
                ]);

                $stock->quantity = max(0, $stock->quantity - $doItem->quantity);
                $stock->save();
            }

            $deliveryOrder->update(['status' => 'delivered']);

            DB::commit();

            return back()->with('success', __('Delivery order has been marked as delivered and stock has been deducted.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function edit(DeliveryOrder $deliveryOrder)
    {
        if (!Auth::user()->can('edit-delivery-orders') || $deliveryOrder->created_by != creatorId()) {
            return redirect()->route('delivery-orders.index')->with('error', __('Permission denied'));
        }

        if ($deliveryOrder->status !== 'draft') {
            return redirect()->route('delivery-orders.index')->with('error', __('Only draft delivery orders can be edited.'));
        }

        $deliveryOrder->load(['items.salesOrderItem.product', 'salesOrder.items.product']);
        $salesOrders = SalesOrder::with(['customer'])
            ->where('created_by', creatorId())
            ->whereIn('status', ['confirmed', 'partially_delivered'])
            ->orWhere('id', $deliveryOrder->sales_order_id)
            ->get();

        $warehouses = Warehouse::where('is_active', true)->select('id', 'name', 'address')->where('created_by', creatorId())->get();

        return Inertia::render('DeliveryOrders/Edit', [
            'order' => $deliveryOrder,
            'salesOrders' => $salesOrders,
            'warehouses' => $warehouses,
        ]);
    }

    public function update(Request $request, DeliveryOrder $deliveryOrder)
    {
        if (!Auth::user()->can('edit-delivery-orders') || $deliveryOrder->created_by != creatorId()) {
            return redirect()->route('delivery-orders.index')->with('error', __('Permission denied'));
        }

        if ($deliveryOrder->status !== 'draft') {
            return redirect()->route('delivery-orders.index')->with('error', __('Only draft delivery orders can be updated.'));
        }

        $validated = $request->validate([
            'delivery_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.sales_order_item_id' => 'required|exists:sales_order_items,id',
            'items.*.product_service_id' => 'required|exists:product_service_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $salesOrder = $deliveryOrder->salesOrder()->with('items')->first();

        $totalQuantity = 0;
        $soItemUpdates = [];

        foreach ($validated['items'] as $itemData) {
            $soItem = $salesOrder->items->firstWhere('id', $itemData['sales_order_item_id']);
            if (!$soItem) {
                return back()->with('error', __('Invalid sales order item.'));
            }

            $remaining = $soItem->remainingQuantity() + ($deliveryOrder->items->where('sales_order_item_id', $soItem->id)->sum('quantity'));
            if ($itemData['quantity'] > $remaining) {
                return back()->with('error', __('Quantity exceeds available for item.'));
            }

            $totalQuantity += $itemData['quantity'];
            $soItemUpdates[$soItem->id] = ($soItemUpdates[$soItem->id] ?? 0) + $itemData['quantity'];
        }

        try {
            DB::beginTransaction();

            foreach ($deliveryOrder->items as $oldItem) {
                $soItem = $salesOrder->items->firstWhere('id', $oldItem->sales_order_item_id);
                if ($soItem) {
                    $soItem->update([
                        'delivered_quantity' => max(0, $soItem->delivered_quantity - $oldItem->quantity),
                    ]);
                }
            }

            $deliveryOrder->items()->delete();

            foreach ($validated['items'] as $itemData) {
                DeliveryOrderItem::create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'sales_order_item_id' => $itemData['sales_order_item_id'],
                    'product_service_id' => $itemData['product_service_id'],
                    'quantity' => $itemData['quantity'],
                ]);
            }

            foreach ($soItemUpdates as $soItemId => $qty) {
                $soItem = $salesOrder->items->firstWhere('id', $soItemId);
                $soItem->update([
                    'delivered_quantity' => $soItem->delivered_quantity + $qty,
                ]);
            }

            $deliveryOrder->update([
                'delivery_date' => $validated['delivery_date'],
                'total_quantity' => $totalQuantity,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->updateSalesOrderStatus($salesOrder);

            DB::commit();

            return redirect()->route('delivery-orders.index')->with('success', __('Delivery order has been updated successfully.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy(DeliveryOrder $deliveryOrder)
    {
        if (!Auth::user()->can('delete-delivery-orders')) {
            return redirect()->route('delivery-orders.index')->with('error', __('Permission denied'));
        }

        if ($deliveryOrder->status !== 'draft') {
            return back()->with('error', __('Only draft delivery orders can be deleted.'));
        }

        try {
            DB::beginTransaction();

            $salesOrder = $deliveryOrder->salesOrder()->with('items')->first();

            foreach ($deliveryOrder->items as $doItem) {
                $soItem = $salesOrder->items->firstWhere('id', $doItem->sales_order_item_id);
                if ($soItem) {
                    $soItem->update([
                        'delivered_quantity' => max(0, $soItem->delivered_quantity - $doItem->quantity),
                    ]);
                }
            }

            $deliveryOrder->items()->delete();
            $deliveryOrder->delete();

            $this->updateSalesOrderStatus($salesOrder);

            DB::commit();

            return redirect()->route('delivery-orders.index')->with('success', __('Delivery order has been deleted.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    private function updateSalesOrderStatus(SalesOrder $salesOrder): void
    {
        $salesOrder->load('items');

        $allFullyDelivered = $salesOrder->items->every(function ($item) {
            return $item->delivered_quantity >= $item->quantity;
        });

        if ($allFullyDelivered) {
            $salesOrder->update(['status' => 'delivered']);
        } elseif ($salesOrder->items->where('delivered_quantity', '>', 0)->count() > 0) {
            $salesOrder->update(['status' => 'partially_delivered']);
        } else {
            $salesOrder->update(['status' => 'confirmed']);
        }
    }
}
