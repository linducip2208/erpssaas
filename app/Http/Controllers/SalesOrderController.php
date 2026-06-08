<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Warehouse;
use Workdo\Account\Models\Customer;
use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\ProductService\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-sales-orders')) {
            $query = SalesOrder::with(['customer', 'warehouse'])
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
                $query->where('order_number', 'like', '%' . $request->search . '%');
            }
            if ($request->date_range) {
                $dates = explode(' - ', $request->date_range);
                if (count($dates) === 2) {
                    $query->whereBetween('order_date', [$dates[0], $dates[1]]);
                }
            }

            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');

            $allowedSortFields = ['order_number', 'order_date', 'expected_delivery_date', 'subtotal', 'tax_amount', 'total_amount', 'status', 'created_at'];
            if (!in_array($sortField, $allowedSortFields) || empty($sortField)) {
                $sortField = 'created_at';
            }

            $query->orderBy($sortField, $sortDirection);

            $perPage = $request->get('per_page', 10);
            $orders = $query->paginate($perPage);
            $customers = Customer::where('created_by', creatorId())->select('id', 'company_name', 'contact_person_name')->get();
            $warehouses = Warehouse::where('is_active', true)->select('id', 'name')->where('created_by', creatorId())->get();

            return Inertia::render('SalesOrders/Index', [
                'orders' => $orders,
                'customers' => $customers,
                'warehouses' => $warehouses,
                'filters' => $request->only(['customer_id', 'warehouse_id', 'status', 'search', 'date_range']),
            ]);
        }

        return back()->with('error', __('Permission denied'));
    }

    public function create()
    {
        if (Auth::user()->can('create-sales-orders')) {
            $customers = Customer::where('created_by', creatorId())->select('id', 'company_name', 'contact_person_name')->get();
            $warehouses = Warehouse::where('is_active', true)->select('id', 'name', 'address')->where('created_by', creatorId())->get();

            return Inertia::render('SalesOrders/Create', [
                'customers' => $customers,
                'warehouses' => $warehouses,
            ]);
        }

        return back()->with('error', __('Permission denied'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('create-sales-orders')) {
            return redirect()->route('sales-orders.index')->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_service_id' => 'required|exists:product_service_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $totals = $this->calculateTotals($validated['items']);

        $order = new SalesOrder();
        $order->order_date = $validated['order_date'];
        $order->expected_delivery_date = $validated['expected_delivery_date'] ?? null;
        $order->customer_id = $validated['customer_id'];
        $order->warehouse_id = $validated['warehouse_id'];
        $order->subtotal = $totals['subtotal'];
        $order->tax_amount = $totals['tax_amount'];
        $order->discount_amount = $totals['discount_amount'];
        $order->total_amount = $totals['total_amount'];
        $order->status = 'draft';
        $order->notes = $validated['notes'] ?? null;
        $order->created_by = creatorId();
        $order->save();

        $this->createOrderItems($order->id, $validated['items']);

        return redirect()->route('sales-orders.index')->with('success', __('Sales order has been created successfully.'));
    }

    public function show(SalesOrder $salesOrder)
    {
        if (!Auth::user()->can('view-sales-orders') || $salesOrder->created_by != creatorId()) {
            return redirect()->route('sales-orders.index')->with('error', __('Permission denied'));
        }

        $salesOrder->load(['customer', 'warehouse', 'items.product', 'deliveryOrders.items', 'creator']);

        return Inertia::render('SalesOrders/Show', [
            'order' => $salesOrder,
        ]);
    }

    public function edit(SalesOrder $salesOrder)
    {
        if (!Auth::user()->can('edit-sales-orders') || $salesOrder->created_by != creatorId()) {
            return redirect()->route('sales-orders.index')->with('error', __('Permission denied'));
        }

        if ($salesOrder->status !== 'draft') {
            return redirect()->route('sales-orders.index')->with('error', __('Only draft orders can be edited.'));
        }

        $salesOrder->load(['items.product']);
        $customers = Customer::where('created_by', creatorId())->select('id', 'company_name', 'contact_person_name')->get();
        $warehouses = Warehouse::where('is_active', true)->select('id', 'name', 'address')->where('created_by', creatorId())->get();

        return Inertia::render('SalesOrders/Edit', [
            'order' => $salesOrder,
            'customers' => $customers,
            'warehouses' => $warehouses,
        ]);
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        if (!Auth::user()->can('edit-sales-orders') || $salesOrder->created_by != creatorId()) {
            return redirect()->route('sales-orders.index')->with('error', __('Permission denied'));
        }

        if ($salesOrder->status !== 'draft') {
            return redirect()->route('sales-orders.index')->with('error', __('Only draft orders can be updated.'));
        }

        $validated = $request->validate([
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_service_id' => 'required|exists:product_service_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $totals = $this->calculateTotals($validated['items']);

        $salesOrder->order_date = $validated['order_date'];
        $salesOrder->expected_delivery_date = $validated['expected_delivery_date'] ?? null;
        $salesOrder->customer_id = $validated['customer_id'];
        $salesOrder->warehouse_id = $validated['warehouse_id'];
        $salesOrder->subtotal = $totals['subtotal'];
        $salesOrder->tax_amount = $totals['tax_amount'];
        $salesOrder->discount_amount = $totals['discount_amount'];
        $salesOrder->total_amount = $totals['total_amount'];
        $salesOrder->notes = $validated['notes'] ?? null;
        $salesOrder->save();

        $salesOrder->items()->delete();
        $this->createOrderItems($salesOrder->id, $validated['items']);

        return redirect()->route('sales-orders.index')->with('success', __('Sales order has been updated successfully.'));
    }

    public function destroy(SalesOrder $salesOrder)
    {
        if (!Auth::user()->can('delete-sales-orders')) {
            return redirect()->route('sales-orders.index')->with('error', __('Permission denied'));
        }

        if ($salesOrder->status !== 'draft') {
            return back()->with('error', __('Only draft orders can be deleted.'));
        }

        $salesOrder->items()->delete();
        $salesOrder->delete();

        return redirect()->route('sales-orders.index')->with('success', __('Sales order has been deleted.'));
    }

    public function confirm(SalesOrder $salesOrder)
    {
        if (!Auth::user()->can('manage-sales-orders')) {
            return back()->with('error', __('Permission denied'));
        }

        if ($salesOrder->status !== 'draft') {
            return back()->with('error', __('Only draft orders can be confirmed.'));
        }

        if ($salesOrder->items()->count() === 0) {
            return back()->with('error', __('Cannot confirm an order with no items.'));
        }

        $salesOrder->update(['status' => 'confirmed']);

        return back()->with('success', __('Sales order has been confirmed.'));
    }

    public function generateDelivery(SalesOrder $salesOrder)
    {
        if (!Auth::user()->can('manage-sales-orders')) {
            return back()->with('error', __('Permission denied'));
        }

        if (!in_array($salesOrder->status, ['confirmed', 'partially_delivered'])) {
            return back()->with('error', __('Can only generate delivery from confirmed or partially delivered orders.'));
        }

        $deliverableItems = $salesOrder->items()
            ->whereRaw('delivered_quantity < quantity')
            ->with('product')
            ->get();

        if ($deliverableItems->isEmpty()) {
            return back()->with('error', __('All items have been fully delivered.'));
        }

        try {
            DB::beginTransaction();

            $totalQuantity = $deliverableItems->sum(function ($item) {
                return $item->remainingQuantity();
            });

            $deliveryOrder = new DeliveryOrder();
            $deliveryOrder->delivery_date = now()->toDateString();
            $deliveryOrder->sales_order_id = $salesOrder->id;
            $deliveryOrder->customer_id = $salesOrder->customer_id;
            $deliveryOrder->warehouse_id = $salesOrder->warehouse_id;
            $deliveryOrder->total_quantity = $totalQuantity;
            $deliveryOrder->status = 'draft';
            $deliveryOrder->created_by = creatorId();
            $deliveryOrder->save();

            foreach ($deliverableItems as $soItem) {
                $remainingQty = $soItem->remainingQuantity();

                DeliveryOrderItem::create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'sales_order_item_id' => $soItem->id,
                    'product_service_id' => $soItem->product_service_id,
                    'quantity' => $remainingQty,
                ]);

                $soItem->update([
                    'delivered_quantity' => $soItem->delivered_quantity + $remainingQty,
                ]);
            }

            $this->updateSalesOrderStatus($salesOrder);

            DB::commit();

            return redirect()->route('delivery-orders.show', $deliveryOrder->id)
                ->with('success', __('Delivery order has been generated successfully.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function getProducts(Request $request)
    {
        if (!Auth::user()->can('create-sales-orders') && !Auth::user()->can('edit-sales-orders')) {
            return response()->json([], 403);
        }

        $warehouseId = $request->warehouse_id;
        if (!$warehouseId) {
            return response()->json([]);
        }

        $products = ProductServiceItem::select('id', 'name', 'sku', 'sale_price', 'tax_ids', 'unit', 'type')
            ->where('is_active', true)
            ->where('created_by', creatorId())
            ->whereHas('warehouseStocks', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                  ->where('quantity', '>', 0);
            })
            ->with(['warehouseStocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }])
            ->get()
            ->map(function ($product) {
                $stock = $product->warehouseStocks->first();
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'sale_price' => $product->sale_price,
                    'unit' => $product->unit,
                    'type' => $product->type,
                    'stock_quantity' => $stock ? $stock->quantity : 0,
                ];
            });

        return response()->json($products);
    }

    private function calculateTotals($items)
    {
        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $discountAmount = ($lineTotal * ($item['discount_percentage'] ?? 0)) / 100;
            $afterDiscount = $lineTotal - $discountAmount;
            $taxRate = $item['tax_rate'] ?? 0;
            $taxAmount = ($afterDiscount * $taxRate) / 100;

            $subtotal += $lineTotal;
            $totalDiscount += $discountAmount;
            $totalTax += $taxAmount;
        }

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $totalTax,
            'discount_amount' => $totalDiscount,
            'total_amount' => $subtotal + $totalTax - $totalDiscount,
        ];
    }

    private function createOrderItems($orderId, $items)
    {
        foreach ($items as $itemData) {
            $lineTotal = $itemData['quantity'] * $itemData['unit_price'];
            $discountAmount = ($lineTotal * ($itemData['discount_percentage'] ?? 0)) / 100;
            $afterDiscount = $lineTotal - $discountAmount;
            $taxRate = $itemData['tax_rate'] ?? 0;
            $taxAmount = ($afterDiscount * $taxRate) / 100;

            SalesOrderItem::create([
                'sales_order_id' => $orderId,
                'product_service_id' => $itemData['product_service_id'],
                'quantity' => $itemData['quantity'],
                'delivered_quantity' => 0,
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total_amount' => $afterDiscount + $taxAmount,
            ]);
        }
    }

    private function updateSalesOrderStatus(SalesOrder $salesOrder): void
    {
        $allFullyDelivered = $salesOrder->items()->whereRaw('delivered_quantity < quantity')->count() === 0;

        if ($allFullyDelivered) {
            $salesOrder->update(['status' => 'delivered']);
        } elseif ($salesOrder->items()->where('delivered_quantity', '>', 0)->count() > 0) {
            $salesOrder->update(['status' => 'partially_delivered']);
        }
    }
}
