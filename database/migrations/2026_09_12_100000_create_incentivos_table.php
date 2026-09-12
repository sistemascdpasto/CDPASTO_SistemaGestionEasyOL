<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incentivos', function (Blueprint $table) {
            $table->id();

            // Relación con el colaborador (únicamente se guardan filas con cédula existente)
            $table->foreignId('colaborador_id')
                ->constrained('colaboradores')
                ->cascadeOnDelete();

            // Campos del Excel
            $table->string('mes', 20)->nullable();
            $table->string('cedula', 30)->nullable();
            $table->string('nombre', 150)->nullable();
            $table->string('cargo', 150)->nullable();

            // Indicador 1
            $table->string('indicador_1', 255)->nullable();
            $table->string('pilar_1', 150)->nullable();
            $table->decimal('total_1', 10, 4)->nullable();
            $table->decimal('meta_1', 10, 4)->nullable();

            // Indicador 2
            $table->string('indicador_2', 255)->nullable();
            $table->string('pilar_2', 150)->nullable();
            $table->decimal('total_2', 10, 4)->nullable();
            $table->decimal('meta_2', 10, 4)->nullable();

            // Indicador 3
            $table->string('indicador_3', 255)->nullable();
            $table->string('pilar_3', 150)->nullable();
            $table->decimal('total_3', 10, 4)->nullable();
            $table->decimal('meta_3', 10, 4)->nullable();

            // Resultado final
            $table->string('podium', 150)->nullable();

            // Valores por indicador y totalizador adicional
            $table->decimal('valor_indicador_1', 10, 4)->nullable();
            $table->decimal('valor_indicador_2', 10, 4)->nullable();
            $table->decimal('valor_indicador_3', 10, 4)->nullable();
            $table->decimal('total_4', 10, 4)->nullable();
            $table->decimal('meta_4',  10, 4)->nullable();

            $table->timestamps();

            // Índice para facilitar búsquedas y evitar duplicados
            $table->index(['colaborador_id', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incentivos');
    }
};
