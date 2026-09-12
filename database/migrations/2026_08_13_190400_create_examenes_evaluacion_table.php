<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examenes_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_medica_id')->constrained('evaluaciones_medicas')->cascadeOnDelete();
            $table->foreignId('examen_id')->constrained('examenes');
            $table->boolean('obligatorio')->default(true);
            $table->string('origen')->default('matriz'); // matriz | adicional
            $table->string('estado')->default('pendiente'); // pendiente | programado | realizado
            $table->date('fecha_programacion')->nullable();
            $table->date('fecha_ejecucion')->nullable();
            $table->string('soporte_path')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->unique(['evaluacion_medica_id', 'examen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examenes_evaluacion');
    }
};
