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
        Schema::create('ausentismos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')
                ->nullable()
                ->constrained('colaboradores')
                ->nullOnDelete();
            
            $table->string('apellidos')->nullable();
            $table->string('nombres')->nullable();
            $table->string('identificador');
            $table->string('grupo')->nullable();
            $table->date('fecha');
            $table->string('permiso')->nullable();
            $table->string('turno')->nullable();
            
            // Bloque 1 de marcación
            $table->string('entro_1')->nullable();
            $table->string('atraso_1')->nullable();
            $table->string('salio_1')->nullable();
            $table->string('adelanto_1')->nullable();
            
            // Bloque 2 de marcación
            $table->string('entro_2')->nullable();
            $table->string('atraso_2')->nullable();
            $table->string('salio_2')->nullable();
            $table->string('adelanto_2')->nullable();

            $table->timestamps();

            $table->index(['identificador', 'fecha']);
            $table->index('grupo');
            $table->index('fecha');
            $table->index('permiso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ausentismos');
    }
};
