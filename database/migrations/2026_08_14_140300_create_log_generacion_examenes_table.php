<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_generacion_examenes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->dateTime('fecha_evento');
            $table->string('resultado');
            $table->text('detalle')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_generacion_examenes');
    }
};
