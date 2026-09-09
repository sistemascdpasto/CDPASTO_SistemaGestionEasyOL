<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignacion_conductores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->nullable()->constrained('colaboradores')->nullOnDelete();
            $table->string('cedula')->nullable();
            $table->string('experiencia_conduccion_camiones_externa')->nullable();
            $table->string('experiencia_total_operacion_interna')->nullable();
            $table->string('tiempo_dos_anos_conductor_externa')->nullable();
            $table->string('experiencia_terreno_plano')->nullable();
            $table->string('experiencia_terreno_montañoso')->nullable();
            $table->string('nivel_skap')->nullable();
            $table->string('participa_reportes_aci')->nullable();
            $table->string('historico_accidentes_incidentes')->nullable();
            $table->string('uso_bebidas_alcoholicas')->nullable();
            $table->string('uso_cigarrillos')->nullable();
            $table->string('uso_medicamentos_controlados')->nullable();
            $table->string('obesidad')->nullable();
            $table->string('problemas_salud_diagnosticados')->nullable();
            $table->string('restricciones_resultados_emo')->nullable();
            $table->string('curso_manejo_defensivo')->nullable();
            $table->string('certificado_escuela_pilotos')->nullable();
            $table->string('comparendos')->nullable();
            $table->string('eventos_criticos_telemetria')->nullable();
            $table->string('adherencia_checklist_preoperacional')->nullable();
            $table->string('entrenamiento_rutas_criticas')->nullable();
            $table->string('prueba_alcohol_positiva_mes')->nullable();
            $table->string('capacitacion_brigadista')->nullable();
            $table->string('owd_cumplimiento_prestartas')->nullable();
            $table->string('entrenamiento_caja_cambios')->nullable();
            $table->string('entrenamiento_frenos')->nullable();
            $table->string('entrenamiento_no_neutro')->nullable();
            $table->string('cumplimiento')->nullable();
            $table->string('apto_rutas_criticas')->nullable();
            $table->string('programar_rutas')->nullable();
            $table->string('rutas_cd')->nullable();
            $table->string('criticidad_matriz_rutas')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignacion_conductores');
    }
};
