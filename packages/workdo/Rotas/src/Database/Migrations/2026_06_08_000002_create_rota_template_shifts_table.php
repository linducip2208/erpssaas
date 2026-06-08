<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rota_template_shifts')) {
            Schema::create('rota_template_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('rota_templates')->onDelete('cascade');
                $table->integer('day_of_week');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('role')->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rota_template_shifts');
    }
};
