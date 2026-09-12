<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluaciones_medicas', function (Blueprint $table) {
            // HU-036: distingue cada periódico consecutivo del colaborador.
            $table->unsignedTinyInteger('numero_periodo')->nullable()->after('tipo_evaluacion');

            // HU-034/035/037: vencimiento propio de esta fila — reemplaza el
            // cálculo por "mirar hacia atrás" que hacía EvaluacionMedicaService::fechaLimite().
            $table->date('fecha_limite')->nullable()->after('proximo_examen_fecha');

            // HU-038: rechazo del examen de egreso.
            $table->string('tipo_cierre')->nullable()->after('estado');
            $table->date('fecha_rechazo')->nullable()->after('tipo_cierre');
            $table->text('observacion_rechazo')->nullable()->after('fecha_rechazo');

            // HU-038: hoy `ejecutarEgreso` no admite archivo.
            $table->string('soporte_path')->nullable()->after('fecha_examen_ejecutado');

            // HU-033: seguimiento del examen (aplica a cualquier tipo_evaluacion,
            // no solo egreso — reutiliza seguimiento_recomendaciones/detalle ya existentes).
            $table->string('estado_seguimiento')->nullable()->after('seguimiento_recomendaciones_detalle');
            $table->string('empresa')->nullable()->after('estado_seguimiento');
            $table->boolean('carta_entregada')->nullable()->after('empresa');
            $table->text('carta_entregada_observacion')->nullable()->after('carta_entregada');
        });
    }

    public function down(): void
    {
        Schema::table('evaluaciones_medicas', function (Blueprint $table) {
            $table->dropColumn([
                'numero_periodo', 'fecha_limite', 'tipo_cierre', 'fecha_rechazo', 'observacion_rechazo',
                'soporte_path', 'estado_seguimiento', 'empresa', 'carta_entregada', 'carta_entregada_observacion',
            ]);
        });
    }
};
