<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_messages')) {
            return;
        }

        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('whatsapp_messages', 'lead_id')) {
                $table->unsignedBigInteger('lead_id')->nullable()->after('id');
                $table->index('lead_id');
            }

            if (! Schema::hasColumn('whatsapp_messages', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('lead_id');
                $table->index('user_id');
            }

            if (! Schema::hasColumn('whatsapp_messages', 'direction')) {
                $table->string('direction', 10)->nullable()->after('phone');
            }

            if (! Schema::hasColumn('whatsapp_messages', 'body')) {
                $table->text('body')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('whatsapp_messages', 'wa_message_id')) {
                $table->string('wa_message_id')->nullable()->after('body');
                $table->index('wa_message_id');
            }

            if (! Schema::hasColumn('whatsapp_messages', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('wa_message_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_messages')) {
            return;
        }

        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            if (Schema::hasColumn('whatsapp_messages', 'lead_id')) {
                $table->dropIndex(['lead_id']);
                $table->dropColumn('lead_id');
            }

            if (Schema::hasColumn('whatsapp_messages', 'user_id')) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('whatsapp_messages', 'direction')) {
                $table->dropColumn('direction');
            }

            if (Schema::hasColumn('whatsapp_messages', 'body')) {
                $table->dropColumn('body');
            }

            if (Schema::hasColumn('whatsapp_messages', 'wa_message_id')) {
                $table->dropIndex(['wa_message_id']);
                $table->dropColumn('wa_message_id');
            }

            if (Schema::hasColumn('whatsapp_messages', 'sent_at')) {
                $table->dropColumn('sent_at');
            }
        });
    }
};

