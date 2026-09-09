<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuesta_morbilidad_preguntas', function (Blueprint $table) {
            $table->id();
            $table->integer('numero_pregunta')->unique();
            $table->integer('seccion_numero');
            $table->string('seccion_titulo');
            $table->text('texto');
            $table->string('tipo');
            $table->boolean('obligatorio')->default(true);
            $table->json('opciones')->nullable();
            $table->boolean('con_otro')->default(false);
            $table->string('segmento')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Poblado inicial a partir de config/morbilidad.php
        $secciones = config('morbilidad.secciones', []);

        foreach ($secciones as $numSeccion => $seccion) {
            $ordenSeccion = 0;
            foreach ($seccion['preguntas'] as $numPregunta => $pregunta) {
                DB::table('encuesta_morbilidad_preguntas')->insert([
                    'numero_pregunta' => $numPregunta,
                    'seccion_numero'  => $numSeccion,
                    'seccion_titulo'  => $seccion['titulo'],
                    'texto'           => $pregunta['texto'],
                    'tipo'            => $pregunta['tipo'],
                    'obligatorio'     => $pregunta['obligatorio'] ?? ($pregunta['tipo'] !== 'texto_libre'),
                    'opciones'        => isset($pregunta['opciones']) ? json_encode($pregunta['opciones']) : null,
                    'con_otro'        => $pregunta['conOtro'] ?? false,
                    'segmento'        => $pregunta['segmento'] ?? null,
                    'orden'           => $ordenSeccion++,
                    'activo'          => true,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('encuesta_morbilidad_preguntas');
    }
};
