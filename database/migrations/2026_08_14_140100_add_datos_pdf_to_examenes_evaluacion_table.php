<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examenes_evaluacion', function (Blueprint $table) {
            // HU-033: dato informativo extraído automáticamente del PDF cargado.
            // Nunca sobrescribe `colaboradores.fecha_ingreso_empresa` (CA-033.10).
            $table->date('fecha_ingreso_pdf')->nullable()->after('soporte_path');
            $table->string('hora_ingreso_pdf')->nullable()->after('fecha_ingreso_pdf');
        });
    }

    public function down(): void
    {
        Schema::table('examenes_evaluacion', function (Blueprint $table) {
            $table->dropColumn(['fecha_ingreso_pdf', 'hora_ingreso_pdf']);
        });
    }
};
