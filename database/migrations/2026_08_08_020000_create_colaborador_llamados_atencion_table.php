<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colaborador_llamados_atencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')
                ->constrained('colaboradores')
                ->cascadeOnDelete();
            $table->text('observacion');
            $table->string('path')->nullable();
            $table->foreignId('registrado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('fecha_hora');
            $table->timestamps();

            $table->index(['colaborador_id', 'fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaborador_llamados_atencion');
    }
};
