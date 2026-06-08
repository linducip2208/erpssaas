<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointment_availability')) {
            Schema::create('appointment_availability', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->integer('day_of_week');
                $table->time('start_time');
                $table->time('end_time');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['user_id', 'day_of_week', 'start_time', 'end_time'], 'appt_avail_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_availability');
    }
};
