<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pdr_office_id')->nullable()->after('remember_token');
            $table->string('pdr_asesor_id')->nullable()->after('pdr_office_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pdr_office_id', 'pdr_asesor_id']);
        });
    }
};