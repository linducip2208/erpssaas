<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PURCHASE REQUISITION
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number')->unique();
            $table->date('requisition_date');
            $table->date('required_date')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('status')->default('draft'); // draft, pending_approval, approved, rejected, converted
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->cascadeOnDelete();
            $table->foreignId('product_service_id')->constrained('product_service_items')->restrictOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('estimated_unit_price', 15, 2)->nullable();
            $table->string('purpose')->nullable();
            $table->timestamps();
        });

        // REQUEST FOR QUOTATION
        Schema::create('request_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number')->unique();
            $table->date('rfq_date');
            $table->date('due_date');
            $table->foreignId('purchase_requisition_id')->nullable()->constrained('purchase_requisitions')->nullOnDelete();
            $table->string('status')->default('draft'); // draft, sent, received, compared, awarded
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_quotation_id')->constrained('request_quotations')->cascadeOnDelete();
            $table->foreignId('product_service_id')->constrained('product_service_items')->restrictOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->timestamps();
        });

        Schema::create('rfq_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_quotation_id')->constrained('request_quotations')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->text('response')->nullable();
            $table->string('status')->default('pending'); // pending, responded, awarded
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        // PURCHASE ORDER
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('purchase_requisition_id')->nullable()->constrained('purchase_requisitions')->nullOnDelete();
            $table->foreignId('request_quotation_id')->nullable()->constrained('request_quotations')->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status')->default('draft'); // draft, pending_approval, approved, partially_received, received, cancelled
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_service_id')->constrained('product_service_items')->restrictOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('received_quantity', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();
        });

        // GOODS RECEIPT NOTE
        Schema::create('goods_receipt_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number')->unique();
            $table->date('receipt_date');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->decimal('total_quantity', 15, 2)->default(0);
            $table->string('status')->default('draft'); // draft, received, inspected
            $table->text('inspection_notes')->nullable();
            $table->string('received_by')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_note_id')->constrained('goods_receipt_notes')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->restrictOnDelete();
            $table->foreignId('product_service_id')->constrained('product_service_items')->restrictOnDelete();
            $table->decimal('quantity_received', 15, 2);
            $table->decimal('quantity_accepted', 15, 2)->default(0);
            $table->decimal('quantity_rejected', 15, 2)->default(0);
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
        Schema::dropIfExists('goods_receipt_notes');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('rfq_vendors');
        Schema::dropIfExists('rfq_items');
        Schema::dropIfExists('request_quotations');
        Schema::dropIfExists('purchase_requisition_items');
        Schema::dropIfExists('purchase_requisitions');
    }
};
