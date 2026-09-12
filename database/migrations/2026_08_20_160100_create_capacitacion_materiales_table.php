<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacitacion_materiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carpeta_id')->constrained('capacitacion_carpetas')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('tipo', 50)->default('documento'); // video, presentacion, documento, hoja_calculo, pdf, enlace, etc.
            $table->string('archivo_path')->nullable();
            $table->string('archivo_nombre_original')->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('enlace_externo', 2048)->nullable();
            $table->string('estado', 30)->default('publicado'); // borrador, publicado
            $table->integer('orden')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacitacion_materiales');
    }
};
