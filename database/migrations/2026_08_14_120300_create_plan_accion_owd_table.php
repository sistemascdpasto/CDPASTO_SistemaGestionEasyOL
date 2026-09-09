<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_accion_owd', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evaluacion_owd_pregunta_id')->unique()->constrained('evaluacion_owd_preguntas')->cascadeOnDelete();

            $table->string('estado')->default('Pendiente');
            $table->date('fecha_vencimiento')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index('estado');
            $table->index('fecha_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_accion_owd');
    }
};
