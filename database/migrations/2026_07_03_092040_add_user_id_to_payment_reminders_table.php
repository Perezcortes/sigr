<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paso 1: agregar user_id nullable para poder hacer el backfill sin violar NOT NULL
        Schema::table('payment_reminders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('payment_setting_id')->constrained()->cascadeOnDelete();
        });

        // Paso 2: los recordatorios existentes son del propietario de la renta (único dueño hasta ahora)
        DB::statement('
            UPDATE payment_reminders pr
            INNER JOIN payment_settings ps ON ps.id = pr.payment_setting_id
            INNER JOIN rents r ON r.id = ps.rent_id
            INNER JOIN owners o ON o.id = r.owner_id
            SET pr.user_id = o.user_id
        ');

        // Paso 3: user_id NOT NULL (todo recordatorio pertenece a exactamente un usuario)
        Schema::table('payment_reminders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_reminders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
