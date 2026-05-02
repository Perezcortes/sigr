<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->boolean('notif_recordatorios_email')->default(true)->after('notas_administracion');
            $table->boolean('notif_recordatorios_push')->default(true)->after('notif_recordatorios_email');
            $table->boolean('notif_recordatorios_whatsapp')->default(true)->after('notif_recordatorios_push');

            $table->boolean('notif_reporte_pago_email')->default(true)->after('notif_recordatorios_whatsapp');
            $table->boolean('notif_reporte_pago_push')->default(true)->after('notif_reporte_pago_email');
            $table->boolean('notif_reporte_pago_whatsapp')->default(true)->after('notif_reporte_pago_push');

            $table->boolean('notif_mensajes_email')->default(true)->after('notif_reporte_pago_whatsapp');
            $table->boolean('notif_mensajes_push')->default(true)->after('notif_mensajes_email');
            $table->boolean('notif_mensajes_whatsapp')->default(true)->after('notif_mensajes_push');

            $table->boolean('notif_mantenimiento_email')->default(true)->after('notif_mensajes_whatsapp');
            $table->boolean('notif_mantenimiento_push')->default(true)->after('notif_mantenimiento_email');
            $table->boolean('notif_mantenimiento_whatsapp')->default(true)->after('notif_mantenimiento_push');
        });
    }

    public function down(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->dropColumn([
                'notif_recordatorios_email',
                'notif_recordatorios_push',
                'notif_recordatorios_whatsapp',
                'notif_reporte_pago_email',
                'notif_reporte_pago_push',
                'notif_reporte_pago_whatsapp',
                'notif_mensajes_email',
                'notif_mensajes_push',
                'notif_mensajes_whatsapp',
                'notif_mantenimiento_email',
                'notif_mantenimiento_push',
                'notif_mantenimiento_whatsapp',
            ]);
        });
    }
};
