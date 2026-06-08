<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('api_logs')) {
            Schema::create('api_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('token_id')->nullable()->constrained('api_tokens')->onDelete('set null');
                $table->string('method', 10);
                $table->string('endpoint');
                $table->text('request_data')->nullable();
                $table->integer('response_code')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
