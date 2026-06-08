<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('custom_field_values')) {
            Schema::create('custom_field_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('field_id')->constrained('custom_fields')->onDelete('cascade');
                $table->unsignedBigInteger('target_id');
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
};
