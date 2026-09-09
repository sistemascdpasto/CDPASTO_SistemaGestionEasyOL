<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuestas_morbilidad', function (Blueprint $table) {
            $table->id();

            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->string('estado')->default('borrador');
            $table->dateTime('fecha_hora');
            $table->dateTime('enviado_en')->nullable();

            $table->timestamps();

            $table->index(['colaborador_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuestas_morbilidad');
    }
};
