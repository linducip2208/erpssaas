<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('custom_fields')) {
            Schema::create('custom_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained('custom_field_groups')->onDelete('cascade');
                $table->string('name');
                $table->string('field_type');
                $table->string('label');
                $table->string('placeholder')->nullable();
                $table->boolean('is_required')->default(false);
                $table->json('options')->nullable();
                $table->string('default_value')->nullable();
                $table->integer('sort_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
