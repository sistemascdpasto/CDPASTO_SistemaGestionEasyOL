<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega los campos del Excel de eventos de tripulación.
     *
     * Campos nuevos:
     *   anio, mes          — temporalidad del Excel
     *   rr                 — código RR (regional)
     *   rr_pasto           — código RR Pasto
     *   excesos_tiempo_ruta           — # de Excesos de Tiempo en Ruta
     *   alertas_velocidad_curvas      — # de Alertas de Velocidad en Curvas
     *   adherencia_checklist_pre      — Adherencia check list Pre Operacional (%)
     *   adherencia_checklist_post     — Adherencia check list Post Operacional (%)
     *   rendimiento_combustible       — Rendimiento de Combustible
     *   modulacion                    — Modulación (valor/porcentaje del Excel)
     *   adherencia_tiempo             — % Adherencia al Tiempo
     *   entrega_en_rango              — Entrega en Rango
     *   rechazos                      — Rechazos
     *   rmd                           — RMD
     *
     * Los campos ya existentes (fecha, placa, doc_transporte, documento,
     * nombre, cargo, total_eventos) NO se tocan.
     */
    public function up(): void
    {
        Schema::table('eventos_tripulacion', function (Blueprint $table) {
            // Temporalidad
            $table->smallInteger('anio')->nullable()->after('doc_transporte');
            $table->tinyInteger('mes')->nullable()->after('anio');

            // Identificadores regionales
            $table->string('rr', 100)->nullable()->after('mes');
            $table->string('rr_pasto', 100)->nullable()->after('rr');

            // Indicadores de eventos (enteros)
            $table->unsignedSmallInteger('excesos_tiempo_ruta')->nullable()->after('total_eventos');
            $table->unsignedSmallInteger('alertas_velocidad_curvas')->nullable()->after('excesos_tiempo_ruta');

            // Adherencias (porcentajes, se guardan como decimal ej: 85.50)
            $table->decimal('adherencia_checklist_pre', 6, 2)->nullable()->after('alertas_velocidad_curvas');
            $table->decimal('adherencia_checklist_post', 6, 2)->nullable()->after('adherencia_checklist_pre');

            // Indicadores de desempeño
            $table->decimal('rendimiento_combustible', 8, 4)->nullable()->after('adherencia_checklist_post');
            $table->string('modulacion', 100)->nullable()->after('rendimiento_combustible');
            $table->decimal('adherencia_tiempo', 6, 2)->nullable()->after('modulacion');
            $table->decimal('entrega_en_rango', 6, 2)->nullable()->after('adherencia_tiempo');
            $table->unsignedSmallInteger('rechazos')->nullable()->after('entrega_en_rango');
            $table->string('rmd', 100)->nullable()->after('rechazos');
        });
    }

    public function down(): void
    {
        Schema::table('eventos_tripulacion', function (Blueprint $table) {
            $table->dropColumn([
                'anio',
                'mes',
                'rr',
                'rr_pasto',
                'excesos_tiempo_ruta',
                'alertas_velocidad_curvas',
                'adherencia_checklist_pre',
                'adherencia_checklist_post',
                'rendimiento_combustible',
                'modulacion',
                'adherencia_tiempo',
                'entrega_en_rango',
                'rechazos',
                'rmd',
            ]);
        });
    }
};
