<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('format'); // stripe, paypal, redirect, rest_api, bank_transfer
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->string('base_url')->nullable();
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->json('extra_config')->nullable();
            $table->json('supported_currencies')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_test_mode')->default(true);
            $table->string('logo')->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
