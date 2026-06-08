<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shared_folders')) {
            Schema::create('shared_folders', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('parent_id')->nullable()->constrained('shared_folders')->onDelete('cascade');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_folders');
    }
};
