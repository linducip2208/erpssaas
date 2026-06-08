<?php

namespace App\Http\Controllers;

use App\Models\RequestQuotation;
use App\Models\RfqItem;
use App\Models\RfqVendor;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Workdo\Account\Models\Vendor;
use Workdo\ProductService\Models\ProductServiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RequestQuotationController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage-request-quotations')) {
            return back()->with('error', __('Permission denied'));
        }

        $query = RequestQuotation::with(['requisition', 'creator'])
            ->where('created_by', creatorId());

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->purchase_requisition_id) {
            $query->where('purchase_requisition_id', $request->purchase_requisition_id);
        }
        if ($request->search) {
            $query->where('rfq_number', 'like', '%' . $request->search . '%');
        }
        if ($request->date_range) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) === 2) {
                $query->whereBetween('rfq_date', [$dates[0], $dates[1]]);
            }
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSortFields = ['rfq_number', 'rfq_date', 'due_date', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSortFields) || empty($sortField)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->get('per_page', 10);
        $quotations = $query->paginate($perPage);
        $requisitions = PurchaseRequisition::where('created_by', creatorId())
            ->where('status', 'approved')
            ->select('id', 'pr_number', 'reason')
            ->get();

        return Inertia::render('RequestQuotations/Index', [
            'quotations' => $quotations,
            'requisitions' => $requisitions,
            'filters' => $request->only(['status', 'purchase_requisition_id', 'search', 'date_range']),
        ]);
    }

    public function create(Request $request)
    {
        if (!Auth::user()->can('create-request-quotations')) {
            return back()->with('error', __('Permission denied'));
        }

        $requisitions = PurchaseRequisition::with(['items.product'])
            ->where('created_by', creatorId())
            ->where('status', 'approved')
            ->get();
        $vendors = Vendor::where('created_by', creatorId())
            ->where('is_active', true)
            ->select('id', 'company_name', 'contact_person_name')
            ->get();

        $preselectedPr = null;
        if ($request->purchase_requisition_id) {
            $preselectedPr = PurchaseRequisition::with(['items.product'])
                ->where('id', $request->purchase_requisition_id)
                ->where('created_by', creatorId())
                ->first();
        }

        return Inertia::render('RequestQuotations/Create', [
            'requisitions' => $requisitions,
            'vendors' => $vendors,
            'preselectedPr' => $preselectedPr,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('create-request-quotations')) {
            return redirect()->route('request-quotations.index')->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'rfq_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:rfq_date',
            'purchase_requisition_id' => 'required|exists:purchase_requisitions,id',
            'terms_conditions' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_service_id' => 'required|exists:product_service_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.purpose' => 'nullable|string|max:500',
            'vendors' => 'required|array|min:1',
            'vendors.*' => 'required|exists:vendors,id',
        ]);

        $pr = PurchaseRequisition::findOrFail($validated['purchase_requisition_id']);
        if ($pr->created_by != creatorId()) {
            return redirect()->route('request-quotations.index')->with('error', __('Permission denied'));
        }

        try {
            DB::beginTransaction();

            $rfq = new RequestQuotation();
            $rfq->rfq_date = $validated['rfq_date'];
            $rfq->due_date = $validated['due_date'];
            $rfq->purchase_requisition_id = $validated['purchase_requisition_id'];
            $rfq->status = 'draft';
            $rfq->terms_conditions = $validated['terms_conditions'] ?? null;
            $rfq->notes = $validated['notes'] ?? null;
            $rfq->created_by = creatorId();
            $rfq->save();

            foreach ($validated['items'] as $itemData) {
                RfqItem::create([
                    'request_quotation_id' => $rfq->id,
                    'product_service_id' => $itemData['product_service_id'],
                    'quantity' => $itemData['quantity'],
                    'purpose' => $itemData['purpose'] ?? null,
                ]);
            }

            foreach ($validated['vendors'] as $vendorId) {
                RfqVendor::create([
                    'request_quotation_id' => $rfq->id,
                    'vendor_id' => $vendorId,
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            return redirect()->route('request-quotations.index')->with('success', __('Request for quotation has been created successfully.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function show(RequestQuotation $quotation)
    {
        if (!Auth::user()->can('view-request-quotations') || $quotation->created_by != creatorId()) {
            return redirect()->route('request-quotations.index')->with('error', __('Permission denied'));
        }

        $quotation->load(['requisition', 'items.product', 'vendors.vendor', 'creator']);

        return Inertia::render('RequestQuotations/Show', [
            'quotation' => $quotation,
        ]);
    }

    public function edit(RequestQuotation $quotation)
    {
        if (!Auth::user()->can('edit-request-quotations') || $quotation->created_by != creatorId()) {
            return redirect()->route('request-quotations.index')->with('error', __('Permission denied'));
        }

        if ($quotation->status !== 'draft') {
            return redirect()->route('request-quotations.index')->with('error', __('Only draft RFQs can be edited.'));
        }

        $quotation->load(['items.product', 'vendors.vendor']);
        $requisitions = PurchaseRequisition::with(['items.product'])
            ->where('created_by', creatorId())
            ->where('status', 'approved')
            ->orWhere('id', $quotation->purchase_requisition_id)
            ->get();
        $vendors = Vendor::where('created_by', creatorId())
            ->where('is_active', true)
            ->select('id', 'company_name', 'contact_person_name')
            ->get();

        return Inertia::render('RequestQuotations/Edit', [
            'quotation' => $quotation,
            'requisitions' => $requisitions,
            'vendors' => $vendors,
        ]);
    }

    public function update(Request $request, RequestQuotation $quotation)
    {
        if (!Auth::user()->can('edit-request-quotations') || $quotation->created_by != creatorId()) {
            return redirect()->route('request-quotations.index')->with('error', __('Permission denied'));
        }

        if ($quotation->status !== 'draft') {
            return redirect()->route('request-quotations.index')->with('error', __('Only draft RFQs can be updated.'));
        }

        $validated = $request->validate([
            'rfq_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:rfq_date',
            'purchase_requisition_id' => 'required|exists:purchase_requisitions,id',
            'terms_conditions' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_service_id' => 'required|exists:product_service_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.purpose' => 'nullable|string|max:500',
            'vendors' => 'required|array|min:1',
            'vendors.*' => 'required|exists:vendors,id',
        ]);

        try {
            DB::beginTransaction();

            $quotation->update([
                'rfq_date' => $validated['rfq_date'],
                'due_date' => $validated['due_date'],
                'purchase_requisition_id' => $validated['purchase_requisition_id'],
                'terms_conditions' => $validated['terms_conditions'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $quotation->items()->delete();
            $quotation->vendors()->delete();

            foreach ($validated['items'] as $itemData) {
                RfqItem::create([
                    'request_quotation_id' => $quotation->id,
                    'product_service_id' => $itemData['product_service_id'],
                    'quantity' => $itemData['quantity'],
                    'purpose' => $itemData['purpose'] ?? null,
                ]);
            }

            foreach ($validated['vendors'] as $vendorId) {
                RfqVendor::create([
                    'request_quotation_id' => $quotation->id,
                    'vendor_id' => $vendorId,
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            return redirect()->route('request-quotations.index')->with('success', __('Request for quotation has been updated successfully.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy(RequestQuotation $quotation)
    {
        if (!Auth::user()->can('delete-request-quotations') || $quotation->created_by != creatorId()) {
            return redirect()->route('request-quotations.index')->with('error', __('Permission denied'));
        }

        if ($quotation->status !== 'draft') {
            return back()->with('error', __('Only draft RFQs can be deleted.'));
        }

        $quotation->vendors()->delete();
        $quotation->items()->delete();
        $quotation->delete();

        return redirect()->route('request-quotations.index')->with('success', __('Request for quotation has been deleted.'));
    }

    public function recordResponse(Request $request, RequestQuotation $quotation)
    {
        if (!Auth::user()->can('manage-request-quotations') || $quotation->created_by != creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'vendor_id' => 'required|exists:rfq_vendors,id',
            'total_price' => 'required|numeric|min:0',
            'response' => 'nullable|string|max:5000',
            'status' => 'required|in:responded,declined',
        ]);

        $rfqVendor = RfqVendor::findOrFail($validated['vendor_id']);
        if ($rfqVendor->quotation->id !== $quotation->id) {
            return back()->with('error', __('Invalid vendor for this quotation.'));
        }

        $rfqVendor->update([
            'total_price' => $validated['total_price'],
            'response' => $validated['response'] ?? null,
            'status' => $validated['status'],
            'responded_at' => now(),
        ]);

        if ($quotation->status === 'draft') {
            $quotation->update(['status' => 'sent']);
        }

        return back()->with('success', __('Vendor response has been recorded.'));
    }

    public function compare(RequestQuotation $quotation)
    {
        if (!Auth::user()->can('manage-request-quotations') || $quotation->created_by != creatorId()) {
            return redirect()->route('request-quotations.index')->with('error', __('Permission denied'));
        }

        $quotation->load(['items.product', 'vendors.vendor']);

        return Inertia::render('RequestQuotations/Compare', [
            'quotation' => $quotation,
        ]);
    }

    public function award(Request $request, RequestQuotation $quotation, RfqVendor $vendor)
    {
        if (!Auth::user()->can('create-purchase-orders') || $quotation->created_by != creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($vendor->quotation->id !== $quotation->id) {
            return back()->with('error', __('Invalid vendor for this quotation.'));
        }

        if ($vendor->status === 'declined') {
            return back()->with('error', __('Cannot award to a declined vendor.'));
        }

        $quotation->vendors()->where('id', '!=', $vendor->id)->update(['status' => 'declined']);
        $vendor->update(['status' => 'awarded']);
        $quotation->update(['status' => 'awarded']);

        return back()->with('success', __('Vendor has been awarded the quotation.'));
    }
}
