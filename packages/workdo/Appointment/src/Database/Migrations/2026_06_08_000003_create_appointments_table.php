<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->foreignId('type_id')->nullable()->constrained('appointment_types')->onDelete('set null');
                $table->dateTime('start_datetime');
                $table->dateTime('end_datetime');
                $table->string('attendee_name');
                $table->string('attendee_email');
                $table->string('attendee_phone')->nullable();
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
