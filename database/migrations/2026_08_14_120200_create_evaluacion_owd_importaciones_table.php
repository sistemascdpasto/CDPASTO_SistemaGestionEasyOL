<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluacion_owd_importaciones', function (Blueprint $table) {
            $table->id();

            $table->string('nombre_archivo');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('registros_leidos')->default(0);
            $table->unsignedInteger('evaluaciones_identificadas')->default(0);
            $table->unsignedInteger('registros_nuevos')->default(0);
            $table->unsignedInteger('registros_duplicados')->default(0);
            $table->unsignedInteger('registros_sin_coincidencia_qr')->default(0);
            $table->unsignedInteger('registros_error')->default(0);

            $table->json('columnas_nuevas_detectadas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion_owd_importaciones');
    }
};
