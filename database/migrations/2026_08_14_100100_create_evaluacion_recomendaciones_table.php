<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluacion_recomendaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_medica_id')->constrained('evaluaciones_medicas')->cascadeOnDelete();
            $table->foreignId('recomendacion_id')->constrained('recomendaciones');
            $table->text('observacion')->nullable();
            $table->boolean('activa')->default(true);
            $table->date('fecha_registro');
            $table->timestamps();

            $table->unique(['evaluacion_medica_id', 'recomendacion_id'], 'eval_recomendaciones_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion_recomendaciones');
    }
};
