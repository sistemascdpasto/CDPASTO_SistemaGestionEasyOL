<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('capacitacion_revisiones', function (Blueprint $table) {
            // Índice compuesto para optimizar consultas de ranking y actividad reciente
            $table->index(['material_id', 'revisada_at'], 'idx_material_fecha');
            
            // Índice para filtros de fecha
            $table->index('revisada_at', 'idx_revisada_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('capacitacion_revisiones', function (Blueprint $table) {
            $table->dropIndex('idx_material_fecha');
            $table->dropIndex('idx_revisada_at');
        });
    }
};
