<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('workflows')) {
            Schema::create('workflows', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('trigger_type');
                $table->string('trigger_module');
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('workflow_actions')) {
            Schema::create('workflow_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_id')->index();
                $table->integer('order')->default(0);
                $table->string('action_type');
                $table->string('action_module');
                $table->json('config')->nullable();
                $table->timestamps();

                $table->foreign('workflow_id')->references('id')->on('workflows')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('workflow_logs')) {
            Schema::create('workflow_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_id')->nullable()->index();
                $table->json('trigger_data')->nullable();
                $table->json('action_data')->nullable();
                $table->string('status')->default('pending');
                $table->timestamp('executed_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('workflow_id')->references('id')->on('workflows')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('workflow_logs');
        Schema::dropIfExists('workflow_actions');
        Schema::dropIfExists('workflows');
    }
};
