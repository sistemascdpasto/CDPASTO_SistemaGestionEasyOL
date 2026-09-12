<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacitacion_portal_config', function (Blueprint $table) {
            $table->id();
            $table->string('titulo_hero', 255)->default('Atraemos talento, desarrollamos potencial.');
            $table->string('subtitulo_hero', 500)->nullable();
            $table->string('imagen_hero_path', 1024)->nullable();
            $table->timestamps();
        });

        // Insertar la fila de configuración por defecto
        DB::table('capacitacion_portal_config')->insert([
            'titulo_hero'    => 'Atraemos talento, desarrollamos potencial.',
            'subtitulo_hero' => 'Capacitaciones certificadas para el crecimiento profesional de tu equipo. Aprende a tu ritmo, avanza con propósito.',
            'imagen_hero_path' => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('capacitacion_portal_config');
    }
};
