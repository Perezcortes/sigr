<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('solicitudes_poliza_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rent_id')->nullable()->constrained('rents')->nullOnDelete();
            $table->string('external_reference')->index();
            $table->json('payload_enviado')->nullable();
            $table->string('status')->default('enviado');
            $table->text('mensaje_error')->nullable();
            $table->json('mensaje_webhook')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_poliza_logs');
    }
};
