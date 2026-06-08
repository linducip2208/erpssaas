<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shared_files')) {
            Schema::create('shared_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('folder_id')->constrained('shared_folders')->onDelete('cascade');
                $table->string('file_name');
                $table->string('file_path');
                $table->bigInteger('file_size')->default(0);
                $table->string('mime_type')->nullable();
                $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_files');
    }
};
