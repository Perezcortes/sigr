<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guarantor_requests', function (Blueprint $table) {
            // Liga la solicitud a una Application antes de que exista un Rent
            $table->foreignId('application_id')->nullable()->unique()->after('id')
                ->constrained('applications')->cascadeOnDelete();

            // Actividad económica del fiador, la pedía el Figma y no existía
            $table->string('pagina_internet')->nullable()->after('empresa_trabaja');
            $table->text('actividad_economica')->nullable()->after('pagina_internet');
        });

        // rent_id deja de ser obligatorio: la solicitud existe antes de que haya Rent
        DB::statement("ALTER TABLE guarantor_requests MODIFY COLUMN rent_id BIGINT UNSIGNED NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE guarantor_requests MODIFY COLUMN rent_id BIGINT UNSIGNED NOT NULL");

        Schema::table('guarantor_requests', function (Blueprint $table) {
            $table->dropColumn(['pagina_internet', 'actividad_economica']);
            $table->dropConstrainedForeignId('application_id');
        });
    }
};
