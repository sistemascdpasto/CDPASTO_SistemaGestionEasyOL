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
        Schema::create('colaborador_pruebas_periodo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')
                ->constrained('colaboradores')
                ->cascadeOnDelete();
            $table->string('etapa', 20); // '7_dias', '30_dias', '90_dias'
            $table->boolean('realizada')->default(false);
            $table->timestamp('fecha_realizacion')->nullable();
            $table->foreignId('realizado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['colaborador_id', 'etapa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colaborador_pruebas_periodo');
    }
};
