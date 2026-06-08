<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('whatsapp_templates')) {
            Schema::create('whatsapp_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('language')->default('id');
                $table->string('category')->nullable();
                $table->string('template_id')->nullable();
                $table->text('content');
                $table->string('status')->default('active');
                $table->json('variables')->nullable();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('whatsapp_logs')) {
            Schema::create('whatsapp_logs', function (Blueprint $table) {
                $table->id();
                $table->string('to_number');
                $table->text('message');
                $table->foreignId('template_id')->nullable()->index();
                $table->string('status')->default('pending');
                $table->json('response')->nullable();
                $table->text('error_message')->nullable();
                $table->foreignId('sent_by')->nullable()->index();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('template_id')->references('id')->on('whatsapp_templates')->onDelete('set null');
                $table->foreign('sent_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_logs');
        Schema::dropIfExists('whatsapp_templates');
    }
};
