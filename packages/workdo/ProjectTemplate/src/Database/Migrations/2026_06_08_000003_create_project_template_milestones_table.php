<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_template_milestones')) {
            Schema::create('project_template_milestones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('project_templates')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('order')->default(0);
                $table->integer('due_days_offset')->default(7);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_template_milestones');
    }
};
