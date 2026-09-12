<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_medicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->string('tipo_evaluacion'); // ingreso | periodico
            $table->date('fecha_evaluacion');
            $table->date('proximo_examen_fecha')->nullable();
            $table->date('fecha_entrada_bandeja')->nullable();
            $table->foreignId('concepto_aptitud_id')->nullable()->constrained('conceptos_aptitud')->nullOnDelete();
            $table->string('estado')->default('sin_iniciar'); // sin_iniciar | demorada | en_proceso | terminada
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['colaborador_id', 'tipo_evaluacion']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_medicas');
    }
};
