<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colaborador_cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')
                ->constrained('colaboradores')
                ->cascadeOnDelete();
            $table->string('cargo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('estado', 10)->default('ACTIVO');
            $table->timestamps();

            $table->index(['colaborador_id', 'estado']);
            $table->index(['colaborador_id', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaborador_cargos');
    }
};
