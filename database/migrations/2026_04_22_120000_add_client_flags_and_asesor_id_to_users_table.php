<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_buyer')) {
                $table->boolean('is_buyer')->default(false)->after('is_tenant');
            }
            if (! Schema::hasColumn('users', 'is_seller')) {
                $table->boolean('is_seller')->default(false)->after('is_buyer');
            }
            if (! Schema::hasColumn('users', 'asesor_id')) {
                $table->foreignId('asesor_id')
                    ->nullable()
                    ->after('office_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'asesor_id')) {
                $table->dropConstrainedForeignId('asesor_id');
            }
            if (Schema::hasColumn('users', 'is_seller')) {
                $table->dropColumn('is_seller');
            }
            if (Schema::hasColumn('users', 'is_buyer')) {
                $table->dropColumn('is_buyer');
            }
        });
    }
};
