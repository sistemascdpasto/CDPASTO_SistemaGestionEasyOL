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
        Schema::create('alertas_velocidad_curva', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->nullable()->index();
            $table->string('nombre')->nullable(); // Nombre del vehículo o Placa
            $table->string('alerta')->nullable(); // Tipo de alerta
            $table->float('velocidad')->nullable(); // Velocidad registrada
            $table->string('coordenada')->nullable(); // Ubicación/Coordenadas
            $table->integer('cantidad_eventos')->nullable()->default(0); // Cantidad de incidentes
            $table->integer('mes')->nullable(); // Mes (1-12)
            $table->timestamps();
            $table->index(['fecha', 'mes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alertas_velocidad_curva');
    }
};
