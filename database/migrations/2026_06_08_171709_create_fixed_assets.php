<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('asset_category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->integer('useful_life_years');
            $table->string('depreciation_method')->default('straight_line'); // straight_line, declining_balance
            $table->decimal('depreciation_rate', 5, 2)->nullable();
            $table->date('depreciation_start_date')->nullable();
            $table->decimal('current_book_value', 15, 2);
            $table->string('status')->default('active'); // active, disposed, written_off, under_maintenance
            $table->string('location')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->date('depreciation_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('book_value_before', 15, 2);
            $table->decimal('book_value_after', 15, 2);
            $table->decimal('accumulated_depreciation', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->date('disposal_date');
            $table->decimal('sale_amount', 15, 2)->nullable();
            $table->decimal('book_value', 15, 2);
            $table->decimal('gain_loss', 15, 2);
            $table->string('disposal_method'); // sold, scrapped, donated, lost
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('asset_categories');
    }
};
