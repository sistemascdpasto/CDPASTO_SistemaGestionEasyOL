<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_owd', function (Blueprint $table) {
            $table->id();

            $table->string('bu')->nullable();
            $table->string('pais')->nullable();
            $table->string('region')->nullable();
            $table->string('uen')->nullable();
            $table->string('id_agencia')->nullable();
            $table->string('agencia')->nullable();

            $table->string('evaluador')->nullable();
            $table->string('posicion_evaluador')->nullable();
            $table->string('qr_safety_evaluador')->nullable();
            $table->string('sharp_evaluador')->nullable();
            $table->foreignId('evaluador_colaborador_id')->nullable()->constrained('colaboradores')->nullOnDelete();

            $table->string('evaluado')->nullable();
            $table->string('posicion')->nullable();
            $table->string('qr_safety')->nullable();
            $table->string('sharp')->nullable();
            $table->foreignId('colaborador_id')->nullable()->constrained('colaboradores')->nullOnDelete();

            $table->dateTime('fecha_evaluacion')->nullable();
            $table->string('type')->nullable();
            $table->string('pillar')->nullable();

            // Contadores cacheados: se recalculan al importar para que
            // indicadores/listados no tengan que agregar `evaluacion_owd_preguntas`
            // en cada consulta.
            $table->unsignedInteger('total_preguntas')->default(0);
            $table->unsignedInteger('preguntas_ok')->default(0);
            $table->unsignedInteger('preguntas_no_ok')->default(0);
            $table->unsignedInteger('preguntas_na')->default(0);

            $table->timestamps();

            $table->index('fecha_evaluacion');
            $table->index(['qr_safety_evaluador', 'qr_safety', 'fecha_evaluacion'], 'evaluaciones_owd_agrupacion_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_owd');
    }
};
