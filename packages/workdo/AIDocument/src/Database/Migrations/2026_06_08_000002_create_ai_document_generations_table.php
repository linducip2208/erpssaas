<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_document_generations')) {
            Schema::create('ai_document_generations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('prompt_id')->constrained('ai_document_prompts')->onDelete('cascade');
                $table->json('input_data')->nullable();
                $table->longText('output_content')->nullable();
                $table->integer('input_tokens')->default(0);
                $table->integer('output_tokens')->default(0);
                $table->string('model_used')->nullable();
                $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
                $table->text('error_message')->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_document_generations');
    }
};
