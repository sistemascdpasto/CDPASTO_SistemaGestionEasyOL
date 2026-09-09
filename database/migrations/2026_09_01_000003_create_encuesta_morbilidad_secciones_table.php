<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de metadatos por sección de la encuesta de morbilidad.
 * Permite gestionar título, descripción e imagen de portada por sección
 * sin tocar el catálogo de preguntas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuesta_morbilidad_secciones', function (Blueprint $table) {
            $table->id();
            $table->integer('numero')->unique();          // coincide con la clave del catálogo
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('imagen_portada')->nullable(); // path relativo en storage/
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Poblar desde config/morbilidad.php
        $secciones = config('morbilidad.secciones', []);
        foreach ($secciones as $num => $seccion) {
            DB::table('encuesta_morbilidad_secciones')->insert([
                'numero'       => $num,
                'titulo'       => $seccion['titulo'],
                'descripcion'  => $seccion['descripcion'] ?? null,
                'activo'       => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('encuesta_morbilidad_secciones');
    }
};
