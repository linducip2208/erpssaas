<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_template_tasks')) {
            Schema::create('project_template_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('project_templates')->onDelete('cascade');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('priority')->default('Low');
                $table->integer('order')->default(0);
                $table->decimal('estimated_hours', 5, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_template_tasks');
    }
};
