<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paises', function (Blueprint $table) {
            $table->id();
            $table->string('tag', 20);
            $table->string('name', 200);
            $table->timestamps();
            $table->softDeletes(); 
        });

        $path = database_path('sql/paises.sql');
        
        if (file_exists($path)) {
            $sql = file_get_contents($path);
            
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('paises');
    }
};