<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El selector de "Tipo de prueba" pasa de Pre Ruta/Ruta/Post Ruta a
     * Ingreso/Aleatoria/Salida, y se agrega el campo "Turno" (A, B o C) que se
     * captura junto al tipo al registrar la prueba. `tipo` ya es string libre
     * (ver migración 2026_08_10_090000), así que solo se migran los valores.
     */
    public function up(): void
    {
        Schema::table('pruebas_alcoholemia', function (Blueprint $table) {
            $table->string('turno', 1)->nullable()->after('tipo');
        });

        DB::table('pruebas_alcoholemia')->where('tipo', 'pre_ruta')->update(['tipo' => 'ingreso']);
        DB::table('pruebas_alcoholemia')->where('tipo', 'ruta')->update(['tipo' => 'aleatoria']);
        DB::table('pruebas_alcoholemia')->where('tipo', 'post_ruta')->update(['tipo' => 'salida']);
    }

    public function down(): void
    {
        DB::table('pruebas_alcoholemia')->where('tipo', 'ingreso')->update(['tipo' => 'pre_ruta']);
        DB::table('pruebas_alcoholemia')->where('tipo', 'aleatoria')->update(['tipo' => 'ruta']);
        DB::table('pruebas_alcoholemia')->where('tipo', 'salida')->update(['tipo' => 'post_ruta']);

        Schema::table('pruebas_alcoholemia', function (Blueprint $table) {
            $table->dropColumn('turno');
        });
    }
};
