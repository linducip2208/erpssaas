<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_document_prompts')) {
            Schema::create('ai_document_prompts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('system_prompt')->nullable();
                $table->text('prompt_template');
                $table->decimal('temperature', 3, 2)->default(0.70);
                $table->integer('max_tokens')->default(2000);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_document_prompts');
    }
};
