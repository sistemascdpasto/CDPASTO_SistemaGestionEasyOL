<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pruebas_alcoholemia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores');
            $table->foreignId('alcoholimetro_id')->nullable()->constrained('alcoholimetros')->nullOnDelete();
            $table->enum('tipo', ['entrada', 'ruta', 'salida']);
            $table->decimal('resultado', 6, 3)->nullable();
            $table->boolean('es_positivo')->default(false);
            $table->boolean('consentimiento_aceptado')->default(false);
            $table->dateTime('consentimiento_en')->nullable();
            $table->string('evidencia_path')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('responsable_id')->constrained('users');
            $table->dateTime('fecha_hora');
            $table->dateTime('programada_en')->nullable();
            $table->enum('estado', ['programada', 'realizada', 'cancelada'])->default('realizada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pruebas_alcoholemia');
    }
};
