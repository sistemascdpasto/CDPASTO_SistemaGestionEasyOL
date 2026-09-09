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
        Schema::table('geovictoria_asistencias', function (Blueprint $table) {
            // Campos de la tabla 'marcaciones' de la automatización (ver
            // schema.sql), necesarios para la pestaña "Hoy" del módulo, que
            // replica el reporte de GeoVictoria tal cual (incluye turno,
            // permiso y las columnas HEA/HEC/HNT, no solo los indicadores
            // ya calculados).
            $table->string('permiso', 100)->nullable()->after('grupo');
            $table->string('turno', 100)->nullable()->after('permiso');
            $table->string('hea', 10)->nullable()->after('horas_trabajadas');
            $table->string('hec', 10)->nullable()->after('hea');
            $table->string('hnt', 10)->nullable()->after('hec');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('geovictoria_asistencias', function (Blueprint $table) {
            $table->dropColumn(['permiso', 'turno', 'hea', 'hec', 'hnt']);
        });
    }
};
