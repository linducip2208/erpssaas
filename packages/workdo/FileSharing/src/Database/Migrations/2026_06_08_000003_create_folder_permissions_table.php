<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('folder_permissions')) {
            Schema::create('folder_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('folder_id')->constrained('shared_folders')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->enum('permission', ['read', 'write', 'admin']);
                $table->timestamps();

                $table->unique(['folder_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('folder_permissions');
    }
};
