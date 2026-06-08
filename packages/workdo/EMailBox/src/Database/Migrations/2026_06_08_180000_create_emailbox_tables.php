<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('mailboxes')) {
            Schema::create('mailboxes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('imap_host');
                $table->integer('imap_port')->default(993);
                $table->string('imap_encryption')->default('ssl');
                $table->string('smtp_host')->nullable();
                $table->integer('smtp_port')->nullable()->default(587);
                $table->string('smtp_encryption')->nullable()->default('tls');
                $table->string('username');
                $table->text('password');
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('mailbox_emails')) {
            Schema::create('mailbox_emails', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mailbox_id')->index();
                $table->string('message_id')->nullable()->index();
                $table->string('from_email');
                $table->string('to_email')->nullable();
                $table->string('subject')->nullable();
                $table->longText('body')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->boolean('is_read')->default(false);
                $table->boolean('is_starred')->default(false);
                $table->string('folder')->default('inbox');
                $table->json('attachments')->nullable();
                $table->timestamps();

                $table->foreign('mailbox_id')->references('id')->on('mailboxes')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('mailbox_emails');
        Schema::dropIfExists('mailboxes');
    }
};
