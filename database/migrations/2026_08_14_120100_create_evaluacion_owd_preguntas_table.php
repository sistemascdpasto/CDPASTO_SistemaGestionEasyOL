<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluacion_owd_preguntas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evaluacion_owd_id')->constrained('evaluaciones_owd')->cascadeOnDelete();

            $table->text('proceso')->nullable();
            $table->text('actividad')->nullable();
            $table->text('tarea')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('puntuacion')->nullable();
            $table->decimal('ponderacion', 8, 2)->nullable();
            $table->boolean('requiere_plan_accion')->default(false);
            $table->string('version')->nullable();

            $table->json('datos_adicionales')->nullable();

            $table->timestamps();

            $table->index('puntuacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion_owd_preguntas');
    }
};
