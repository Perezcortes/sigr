<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'buyer_id')) {
                $table->foreignId('buyer_id')->nullable()->constrained('buyers')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales', 'seller_id')) {
                $table->foreignId('seller_id')->nullable()->constrained('sellers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['buyer_id']);
            $table->dropColumn('buyer_id');
            $table->dropForeign(['seller_id']);
            $table->dropColumn('seller_id');
        });
    }
};