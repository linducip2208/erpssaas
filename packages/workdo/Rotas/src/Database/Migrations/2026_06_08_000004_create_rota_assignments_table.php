<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rota_assignments')) {
            Schema::create('rota_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rota_id')->constrained('rotas')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->date('date');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('status')->default('assigned');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rota_assignments');
    }
};
