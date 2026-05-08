<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('buyers')) {
            Schema::create('buyers', function (Blueprint $table) {
                $table->id();
                $table->string('tipo_persona', 32)->nullable();
                $table->string('nombres')->nullable();
                $table->string('ap_paterno')->nullable();
                $table->string('ap_materno')->nullable();
                $table->string('email')->nullable();
                $table->string('celular', 32)->nullable();
                $table->string('telefono', 32)->nullable();
                $table->date('fecha_nacimiento')->nullable();
                $table->string('rfc', 32)->nullable();
                $table->string('curp', 32)->nullable();
                $table->string('calle')->nullable();
                $table->string('colonia')->nullable();
                $table->string('ciudad')->nullable();
                $table->string('estado')->nullable();
                $table->string('cp', 16)->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('historial_acciones')->nullable();
                $table->timestamps();

                $table->index('email');
            });
        }

        if (! Schema::hasTable('sellers')) {
            Schema::create('sellers', function (Blueprint $table) {
                $table->id();
                $table->string('tipo_persona', 32)->nullable();
                $table->string('nombres')->nullable();
                $table->string('ap_paterno')->nullable();
                $table->string('ap_materno')->nullable();
                $table->string('email')->nullable();
                $table->string('celular', 32)->nullable();
                $table->string('telefono', 32)->nullable();
                $table->date('fecha_nacimiento')->nullable();
                $table->string('rfc', 32)->nullable();
                $table->string('curp', 32)->nullable();
                $table->string('calle')->nullable();
                $table->string('colonia')->nullable();
                $table->string('ciudad')->nullable();
                $table->string('estado')->nullable();
                $table->string('cp', 16)->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('historial_acciones')->nullable();
                $table->timestamps();

                $table->index('email');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sellers');
        Schema::dropIfExists('buyers');
    }
};
