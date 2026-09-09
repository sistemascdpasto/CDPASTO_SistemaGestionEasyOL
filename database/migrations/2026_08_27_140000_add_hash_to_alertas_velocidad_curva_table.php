<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alertas_velocidad_curva', function (Blueprint $table) {
            // Hash MD5 para detección rápida de duplicados en importaciones
            $table->string('hash', 32)->nullable()->unique()->after('mes');
        });
    }

    public function down(): void
    {
        Schema::table('alertas_velocidad_curva', function (Blueprint $table) {
            $table->dropColumn('hash');
        });
    }
};
