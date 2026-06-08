<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointment_types')) {
            Schema::create('appointment_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('duration_minutes')->default(30);
                $table->string('color')->default('#6366f1');
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_types');
    }
};
