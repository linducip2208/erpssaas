<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('custom_menus')) {
            Schema::create('custom_menus', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('href')->nullable();
                $table->string('icon')->nullable();
                $table->string('permission')->nullable();
                $table->foreignId('parent_id')->nullable()->constrained('custom_menus')->onDelete('cascade');
                $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_menus');
    }
};
