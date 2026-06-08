<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PRODUCT VARIANTS
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Size, Color, Material
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->string('value'); // e.g. S, M, L / Red, Blue
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_service_id')->constrained('product_service_items')->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('name');
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->foreignId('product_attribute_value_id')->constrained('product_attribute_values')->cascadeOnDelete();
            $table->timestamps();
        });

        // SERIAL/BATCH TRACKING
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_service_id')->constrained('product_service_items')->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('serial_number')->unique();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->string('status')->default('available'); // available, sold, damaged, returned
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_service_id')->constrained('product_service_items')->restrictOnDelete();
            $table->string('batch_number')->unique();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('status')->default('active'); // active, expired, quarantined
            $table->timestamps();
        });

        Schema::create('batch_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->string('movement_type'); // purchase, sale, transfer, adjustment
            $table->decimal('quantity', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // STOCK OPNAME (Physical Count)
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_number')->unique();
            $table->date('opname_date');
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('status')->default('draft'); // draft, in_progress, completed, approved
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('product_service_id')->constrained('product_service_items')->restrictOnDelete();
            $table->decimal('system_quantity', 15, 2);
            $table->decimal('physical_quantity', 15, 2)->nullable();
            $table->decimal('difference', 15, 2)->nullable();
            $table->string('adjustment_type')->nullable(); // surplus, shortage
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // PRODUCT MIN/MAX STOCK
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouse_stocks', 'min_stock')) {
                $table->decimal('min_stock', 15, 2)->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('warehouse_stocks', 'max_stock')) {
                $table->decimal('max_stock', 15, 2)->default(0)->after('min_stock');
            }
            if (!Schema::hasColumn('warehouse_stocks', 'reorder_point')) {
                $table->decimal('reorder_point', 15, 2)->default(0)->after('max_stock');
            }
        });

        // UNIT CONVERSION
        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_service_id')->constrained('product_service_items')->cascadeOnDelete();
            $table->foreignId('from_unit_id')->constrained('product_service_units')->restrictOnDelete();
            $table->foreignId('to_unit_id')->constrained('product_service_units')->restrictOnDelete();
            $table->decimal('conversion_rate', 15, 6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_conversions');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('batch_movements');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('product_variant_attributes');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
    }
};
