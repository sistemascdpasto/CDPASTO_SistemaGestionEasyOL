<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actas_taller_evidencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('acta_taller_id')->constrained('actas_taller')->cascadeOnDelete();

            // path relativo en storage/public
            $table->string('path');

            // Etiqueta descriptiva: Frente, Trasera, Lado izquierdo, etc.
            $table->string('etiqueta', 100)->nullable();

            // Orden de visualización
            $table->unsignedSmallInteger('orden')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actas_taller_evidencias');
    }
};
