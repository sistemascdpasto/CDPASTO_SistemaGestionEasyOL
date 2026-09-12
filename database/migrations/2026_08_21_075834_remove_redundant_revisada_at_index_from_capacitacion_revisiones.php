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
            // Eliminar índice redundante - ya está cubierto por idx_material_fecha
            $table->dropIndex('idx_revisada_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('capacitacion_revisiones', function (Blueprint $table) {
            // Restaurar índice si se hace rollback
            $table->index('revisada_at', 'idx_revisada_at');
        });
    }
};
