<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condiciones_salud', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->enum('momento', ['ingreso', 'salida']);
            $table->enum('estado', ['Bueno', 'Regular', 'Malo']);
            $table->text('observacion')->nullable();
            $table->foreignId('responsable_id')->constrained('users');
            $table->dateTime('fecha_hora');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condiciones_salud');
    }
};
