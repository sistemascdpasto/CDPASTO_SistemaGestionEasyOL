<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('geovictoria_asistencias', function (Blueprint $table) {
            $table->id();
            $table->string('identificador', 50);
            $table->date('fecha');
            $table->string('apellidos', 150)->nullable();
            $table->string('nombres', 150)->nullable();
            $table->string('cargo', 100)->nullable();
            $table->string('grupo', 100)->nullable();
            $table->string('entrada', 10)->nullable();
            $table->string('salida_descanso', 10)->nullable();
            $table->string('ingreso_descanso', 10)->nullable();
            $table->string('salida', 10)->nullable();
            $table->string('horas_trabajadas', 10)->nullable();
            $table->boolean('exceso_jornada')->default(false);
            $table->string('horas_descanso_previo', 40)->nullable();
            $table->boolean('descanso_no_efectivo')->default(false);
            $table->timestamps();

            $table->unique(['identificador', 'fecha']);
            $table->index('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geovictoria_asistencias');
    }
};
