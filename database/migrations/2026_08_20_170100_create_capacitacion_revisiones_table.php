<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacitacion_revisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('capacitacion_materiales')->cascadeOnDelete();
            $table->timestamp('revisada_at')->useCurrent();

            // Restricción única para evitar duplicados en el progreso
            $table->unique(['user_id', 'material_id']);
            $table->index(['user_id', 'revisada_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacitacion_revisiones');
    }
};
