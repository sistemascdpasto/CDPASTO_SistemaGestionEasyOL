<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluaciones_medicas', function (Blueprint $table) {
            $table->date('fecha_programacion')->nullable()->after('emite');
            $table->date('fecha_examen_ejecutado')->nullable()->after('fecha_programacion');
            $table->string('seguimiento_recomendaciones')->nullable()->after('fecha_examen_ejecutado');
            $table->text('seguimiento_recomendaciones_detalle')->nullable()->after('seguimiento_recomendaciones');
        });
    }

    public function down(): void
    {
        Schema::table('evaluaciones_medicas', function (Blueprint $table) {
            $table->dropColumn(['fecha_programacion', 'fecha_examen_ejecutado', 'seguimiento_recomendaciones', 'seguimiento_recomendaciones_detalle']);
        });
    }
};
