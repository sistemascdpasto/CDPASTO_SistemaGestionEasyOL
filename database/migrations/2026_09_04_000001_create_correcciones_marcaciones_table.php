<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correcciones_marcaciones', function (Blueprint $table) {
            $table->id();
            $table->string('identificacion', 50)->index();
            $table->date('fecha')->index();
            $table->string('hora', 20)->nullable();
            $table->string('tipo', 100)->nullable();
            $table->string('centro_costo', 150)->nullable();
            $table->text('comentario')->nullable();

            // Campos trazidos desde la tabla colaboradores (O(1) lookup)
            $table->string('nombre_completo', 255)->nullable();
            $table->string('cargo', 150)->nullable();

            // Banderas de validación
            $table->boolean('colaborador_encontrado')->default(false)->index();
            $table->string('error_validacion', 255)->nullable();

            // Auditoría de la carga
            $table->unsignedBigInteger('usuario_importo_id')->nullable();
            $table->foreign('usuario_importo_id')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            // índice compuesto para búsquedas frecuentes
            $table->index(['identificacion', 'fecha']);
            $table->index(['fecha', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correcciones_marcaciones');
    }
};
