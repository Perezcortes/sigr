<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
        });

        Schema::table('rents', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->nullable()->change();
        });

        Schema::table('rents', function (Blueprint $table) {
            $table->foreign('owner_id')
                ->references('id')
                ->on('owners')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
        });

        Schema::table('rents', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->nullable(false)->change();
        });

        Schema::table('rents', function (Blueprint $table) {
            $table->foreign('owner_id')
                ->references('id')
                ->on('owners')
                ->cascadeOnDelete();
        });
    }
};
