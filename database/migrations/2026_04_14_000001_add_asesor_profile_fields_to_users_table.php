<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono', 20)->nullable()->after('mobile');
            $table->string('whatsapp', 20)->nullable()->after('telefono');
            $table->string('facebook')->nullable()->after('whatsapp');
            $table->string('instagram')->nullable()->after('facebook');
            $table->string('linkedin')->nullable()->after('instagram');
            $table->foreignId('zone_estate_id')->nullable()->after('linkedin')->constrained('estates')->nullOnDelete();
            $table->json('zone_city_ids')->nullable()->after('zone_estate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zone_estate_id');
            $table->dropColumn([
                'telefono',
                'whatsapp',
                'facebook',
                'instagram',
                'linkedin',
                'zone_city_ids',
            ]);
        });
    }
};
