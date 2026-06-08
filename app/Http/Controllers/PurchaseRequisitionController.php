<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Warehouse;
use Workdo\Hrm\Models\Department;
use Workdo\Account\Models\Vendor;
use Workdo\ProductService\Models\ProductServiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseRequisitionController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage-purchase-requisitions')) {
            return back()->with('error', __('Permission denied'));
        }

        $query = PurchaseRequisition::with(['requestedBy', 'department', 'items.product'])
            ->where('created_by', creatorId());

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('pr_number', 'like', '%' . $request->search . '%')
                    ->orWhere('reason', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->date_range) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) === 2) {
                $query->whereBetween('requisition_date', [$dates[0], $dates[1]]);
            }
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSortFields = ['pr_number', 'requisition_date', 'required_date', 'priority', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSortFields) || empty($sortField)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->get('per_page', 10);
        $requisitions = $query->paginate($perPage);
        $departments = Department::where('created_by', creatorId())->select('id', 'department_name')->get();

        return Inertia::render('PurchaseRequisitions/Index', [
            'requisitions' => $requisitions,
            'departments' => $departments,
            'filters' => $request->only(['status', 'department_id', 'priority', 'search', 'date_range']),
        ]);
    }

    public function create()
    {
        if (!Auth::user()->can('create-purchase-requisitions')) {
            return back()->with('error', __('Permission denied'));
        }

        $departments = Department::where('created_by', creatorId())->select('id', 'department_name')->get();
        $users = \App\Models\User::where('created_by', creatorId())
            ->select('id', 'name', 'email')
            ->get();
        $products = ProductServiceItem::where('is_active', true)
            ->where('created_by', creatorId())
            ->select('id', 'name', 'sku', 'unit')
            ->get();

        return Inertia::render('PurchaseRequisitions/Create', [
            'departments' => $departments,
            'users' => $users,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('create-purchase-requisitions')) {
            return redirect()->route('purchase-requisitions.index')->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'requisition_date' => 'required|date',
            'required_date' => 'required|date|after_or_equal:requisition_date',
            'requested_by' => 'required|exists:users,id',
            'department_id' => 'nullable|exists:hrm_departments,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'reason' => 'required|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_service_id' => 'required|exists:product_service_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.estimated_unit_price' => 'nullable|numeric|min:0',
            'items.*.purpose' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $pr = new PurchaseRequisition();
            $pr->requisition_date = $validated['requisition_date'];
            $pr->required_date = $validated['required_date'];
            $pr->requested_by = $validated['requested_by'];
            $pr->department_id = $validated['department_id'] ?? null;
            $pr->priority = $validated['priority'];
            $pr->status = 'draft';
            $pr->reason = $validated['reason'];
            $pr->notes = $validated['notes'] ?? null;
            $pr->created_by = creatorId();
            $pr->save();

            foreach ($validated['items'] as $itemData) {
                PurchaseRequisitionItem::create([
                    'purchase_requisition_id' => $pr->id,
                    'product_service_id' => $itemData['product_service_id'],
                    'quantity' => $itemData['quantity'],
                    'estimated_unit_price' => $itemData['estimated_unit_price'] ?? 0,
                    'purpose' => $itemData['purpose'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-requisitions.index')->with('success', __('Purchase requisition has been created successfully.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function show(PurchaseRequisition $requisition)
    {
        if (!Auth::user()->can('view-purchase-requisitions') || $requisition->created_by != creatorId()) {
            return redirect()->route('purchase-requisitions.index')->with('error', __('Permission denied'));
        }

        $requisition->load(['requestedBy', 'department', 'items.product', 'approvedBy', 'creator', 'requestQuotations', 'purchaseOrders']);

        return Inertia::render('PurchaseRequisitions/Show', [
            'requisition' => $requisition,
        ]);
    }

    public function edit(PurchaseRequisition $requisition)
    {
        if (!Auth::user()->can('edit-purchase-requisitions') || $requisition->created_by != creatorId()) {
            return redirect()->route('purchase-requisitions.index')->with('error', __('Permission denied'));
        }

        if ($requisition->status !== 'draft') {
            return redirect()->route('purchase-requisitions.index')->with('error', __('Only draft requisitions can be edited.'));
        }

        $requisition->load(['requestedBy', 'department', 'items.product']);
        $departments = Department::where('created_by', creatorId())->select('id', 'department_name')->get();
        $users = \App\Models\User::where('created_by', creatorId())->select('id', 'name', 'email')->get();
        $products = ProductServiceItem::where('is_active', true)
            ->where('created_by', creatorId())
            ->select('id', 'name', 'sku', 'unit')
            ->get();

        return Inertia::render('PurchaseRequisitions/Edit', [
            'requisition' => $requisition,
            'departments' => $departments,
            'users' => $users,
            'products' => $products,
        ]);
    }

    public function update(Request $request, PurchaseRequisition $requisition)
    {
        if (!Auth::user()->can('edit-purchase-requisitions') || $requisition->created_by != creatorId()) {
            return redirect()->route('purchase-requisitions.index')->with('error', __('Permission denied'));
        }

        if ($requisition->status !== 'draft') {
            return redirect()->route('purchase-requisitions.index')->with('error', __('Only draft requisitions can be updated.'));
        }

        $validated = $request->validate([
            'requisition_date' => 'required|date',
            'required_date' => 'required|date|after_or_equal:requisition_date',
            'requested_by' => 'required|exists:users,id',
            'department_id' => 'nullable|exists:hrm_departments,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'reason' => 'required|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_service_id' => 'required|exists:product_service_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.estimated_unit_price' => 'nullable|numeric|min:0',
            'items.*.purpose' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $requisition->update([
                'requisition_date' => $validated['requisition_date'],
                'required_date' => $validated['required_date'],
                'requested_by' => $validated['requested_by'],
                'department_id' => $validated['department_id'] ?? null,
                'priority' => $validated['priority'],
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $requisition->items()->delete();

            foreach ($validated['items'] as $itemData) {
                PurchaseRequisitionItem::create([
                    'purchase_requisition_id' => $requisition->id,
                    'product_service_id' => $itemData['product_service_id'],
                    'quantity' => $itemData['quantity'],
                    'estimated_unit_price' => $itemData['estimated_unit_price'] ?? 0,
                    'purpose' => $itemData['purpose'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-requisitions.index')->with('success', __('Purchase requisition has been updated successfully.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy(PurchaseRequisition $requisition)
    {
        if (!Auth::user()->can('delete-purchase-requisitions') || $requisition->created_by != creatorId()) {
            return redirect()->route('purchase-requisitions.index')->with('error', __('Permission denied'));
        }

        if ($requisition->status !== 'draft') {
            return back()->with('error', __('Only draft requisitions can be deleted.'));
        }

        $requisition->items()->delete();
        $requisition->delete();

        return redirect()->route('purchase-requisitions.index')->with('success', __('Purchase requisition has been deleted.'));
    }

    public function submit(PurchaseRequisition $requisition)
    {
        if (!Auth::user()->can('manage-purchase-requisitions') || $requisition->created_by != creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($requisition->status !== 'draft') {
            return back()->with('error', __('Only draft requisitions can be submitted.'));
        }

        $requisition->update(['status' => 'pending_approval']);

        return back()->with('success', __('Purchase requisition has been submitted for approval.'));
    }

    public function approve(PurchaseRequisition $requisition)
    {
        if (!Auth::user()->can('approve-purchase-requisitions') || $requisition->created_by != creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($requisition->status !== 'pending_approval') {
            return back()->with('error', __('Only pending requisitions can be approved.'));
        }

        $requisition->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', __('Purchase requisition has been approved.'));
    }

    public function reject(Request $request, PurchaseRequisition $requisition)
    {
        if (!Auth::user()->can('approve-purchase-requisitions') || $requisition->created_by != creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($requisition->status !== 'pending_approval') {
            return back()->with('error', __('Only pending requisitions can be rejected.'));
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:2000',
        ]);

        $requisition->update([
            'status' => 'rejected',
            'notes' => trim(($requisition->notes ?? '') . "\nRejected: " . ($validated['rejection_reason'] ?? 'No reason provided')),
        ]);

        return back()->with('success', __('Purchase requisition has been rejected.'));
    }

    public function convertToPO(PurchaseRequisition $requisition)
    {
        if (!Auth::user()->can('create-purchase-orders') || $requisition->created_by != creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($requisition->status !== 'approved') {
            return back()->with('error', __('Only approved requisitions can be converted to purchase orders.'));
        }

        $requisition->load(['items.product']);
        $vendors = Vendor::where('created_by', creatorId())
            ->where('is_active', true)
            ->select('id', 'company_name', 'contact_person_name')
            ->get();
        $warehouses = Warehouse::where('is_active', true)
            ->select('id', 'name')
            ->where('created_by', creatorId())
            ->get();

        return Inertia::render('PurchaseOrders/ConvertFromPR', [
            'requisition' => $requisition,
            'vendors' => $vendors,
            'warehouses' => $warehouses,
        ]);
    }
}
