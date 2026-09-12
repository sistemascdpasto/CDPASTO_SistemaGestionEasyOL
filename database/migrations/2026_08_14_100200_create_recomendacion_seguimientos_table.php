<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recomendacion_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_recomendacion_id')->constrained('evaluacion_recomendaciones')->cascadeOnDelete();
            $table->date('fecha_seguimiento');
            $table->string('estado_seguimiento');
            $table->text('observacion')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_proximo_seguimiento')->nullable();
            $table->boolean('carta_recomendacion_entregada')->default(false);
            $table->string('soporte_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recomendacion_seguimientos');
    }
};
