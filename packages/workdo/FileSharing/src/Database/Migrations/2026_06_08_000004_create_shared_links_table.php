<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shared_links')) {
            Schema::create('shared_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('file_id')->constrained('shared_files')->onDelete('cascade');
                $table->string('token')->unique();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_links');
    }
};
