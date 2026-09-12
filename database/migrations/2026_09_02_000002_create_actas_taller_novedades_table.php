<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actas_taller_novedades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('acta_taller_id')->constrained('actas_taller')->cascadeOnDelete();

            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();

            // Categoría / tipo
            $table->string('categoria', 100)->nullable();

            // Prioridad: alta | media | baja
            $table->string('prioridad', 20)->default('media');

            // Estado: pendiente | en_revision | solucionado
            $table->string('estado', 30)->default('pendiente');

            // Responsable (taller u otro)
            $table->string('responsable', 100)->nullable();

            // Fecha de reporte y solución
            $table->date('fecha_reporte')->nullable();
            $table->date('fecha_solucion')->nullable();

            $table->unsignedSmallInteger('orden')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actas_taller_novedades');
    }
};
