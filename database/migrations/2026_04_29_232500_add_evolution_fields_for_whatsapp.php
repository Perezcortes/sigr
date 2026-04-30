<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_instances') && ! Schema::hasColumn('whatsapp_instances', 'qr_code_updated_at')) {
            Schema::table('whatsapp_instances', function (Blueprint $table) {
                $table->timestamp('qr_code_updated_at')->nullable()->after('qr_code');
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'evolution_whatsapp_instance_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignUuid('evolution_whatsapp_instance_id')
                    ->nullable()
                    ->after('whatsapp')
                    ->constrained('whatsapp_instances')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'evolution_whatsapp_instance_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['evolution_whatsapp_instance_id']);
                $table->dropColumn('evolution_whatsapp_instance_id');
            });
        }

        if (Schema::hasTable('whatsapp_instances') && Schema::hasColumn('whatsapp_instances', 'qr_code_updated_at')) {
            Schema::table('whatsapp_instances', function (Blueprint $table) {
                $table->dropColumn('qr_code_updated_at');
            });
        }
    }
};
