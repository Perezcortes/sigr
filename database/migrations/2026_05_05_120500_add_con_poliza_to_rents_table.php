<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rents')) {
            return;
        }

        Schema::table('rents', function (Blueprint $table): void {
            if (! Schema::hasColumn('rents', 'con_poliza')) {
                $table->boolean('con_poliza')->default(false)->after('fecha_firma');
                $table->index('con_poliza');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rents')) {
            return;
        }

        Schema::table('rents', function (Blueprint $table): void {
            if (Schema::hasColumn('rents', 'con_poliza')) {
                $table->dropIndex(['con_poliza']);
                $table->dropColumn('con_poliza');
            }
        });
    }
};
