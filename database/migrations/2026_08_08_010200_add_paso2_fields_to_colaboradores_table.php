<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->string('manejo_defensivo_aplica', 10)->nullable();
            $table->string('conduccion_carga_pesada_aplica', 10)->nullable();
            $table->string('experiencia_terreno_plano', 10)->nullable();
            $table->string('experiencia_terreno_montanoso', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn([
                'manejo_defensivo_aplica',
                'conduccion_carga_pesada_aplica',
                'experiencia_terreno_plano',
                'experiencia_terreno_montanoso',
            ]);
        });
    }
};
