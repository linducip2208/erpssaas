<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('recurring_number')->unique();
            $table->string('type'); // sales, purchase
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('frequency'); // daily, weekly, monthly, quarterly, yearly
            $table->integer('interval')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('recurring_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_invoice_id')->constrained('recurring_invoices')->cascadeOnDelete();
            $table->foreignId('product_service_id')->constrained('product_service_items')->restrictOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('recurring_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('frequency'); // daily, weekly, monthly
            $table->integer('interval')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoices', 'recurring_invoice_id')) {
                $table->foreignId('recurring_invoice_id')->nullable()->after('id')->constrained('recurring_invoices')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_invoices', 'sales_order_id')) {
                $table->foreignId('sales_order_id')->nullable()->after('recurring_invoice_id')->constrained('sales_orders')->nullOnDelete();
            }
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'recurring_invoice_id')) {
                $table->foreignId('recurring_invoice_id')->nullable()->after('id')->constrained('recurring_invoices')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchase_invoices', 'purchase_order_id')) {
                $table->foreignId('purchase_order_id')->nullable()->after('recurring_invoice_id')->constrained('purchase_orders')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['recurring_invoice_id']);
            $table->dropForeign(['sales_order_id']);
            $table->dropColumn(['recurring_invoice_id', 'sales_order_id']);
        });
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['recurring_invoice_id']);
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn(['recurring_invoice_id', 'purchase_order_id']);
        });
        Schema::dropIfExists('recurring_tasks');
        Schema::dropIfExists('recurring_invoice_items');
        Schema::dropIfExists('recurring_invoices');
    }
};
