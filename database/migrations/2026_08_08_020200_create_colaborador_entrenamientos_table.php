<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colaborador_entrenamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')
                ->constrained('colaboradores')
                ->cascadeOnDelete();
            $table->foreignId('entrenamiento_id')
                ->constrained('entrenamientos')
                ->cascadeOnDelete();
            $table->date('fecha_registro');
            $table->time('hora_registro');
            $table->foreignId('registrado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['colaborador_id', 'fecha_registro']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaborador_entrenamientos');
    }
};
