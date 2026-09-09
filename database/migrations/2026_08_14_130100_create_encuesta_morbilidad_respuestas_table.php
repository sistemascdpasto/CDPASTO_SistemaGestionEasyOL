<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuesta_morbilidad_respuestas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('encuesta_morbilidad_id')->constrained('encuestas_morbilidad')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_pregunta');
            $table->string('valor')->nullable();
            $table->text('detalle')->nullable();

            $table->timestamps();

            $table->unique(['encuesta_morbilidad_id', 'numero_pregunta'], 'encuesta_morbilidad_respuestas_unicidad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuesta_morbilidad_respuestas');
    }
};
