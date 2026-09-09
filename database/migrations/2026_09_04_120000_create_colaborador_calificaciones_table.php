<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colaborador_calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->nullable()->constrained('colaboradores')->nullOnDelete();
            $table->string('identificacion', 50)->index();
            $table->string('colaborador', 191)->nullable();
            $table->string('cargo', 150)->nullable();
            $table->string('centro_distribucion', 150)->nullable()->index();
            $table->string('modulo_id_externo', 100)->nullable();
            $table->string('modulo', 191)->index();
            $table->decimal('nota_modulo', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaborador_calificaciones');
    }
};
