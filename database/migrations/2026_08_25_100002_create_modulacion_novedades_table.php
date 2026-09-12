<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulacion_novedades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modulacion_id')->constrained('modulaciones')->cascadeOnDelete();
            $table->foreignId('colaborador_id')->nullable()->constrained('colaboradores')->nullOnDelete();
            $table->string('cedula')->nullable();
            $table->string('nombres')->nullable();
            $table->string('cargo')->nullable();
            $table->string('fecha_reintegro')->nullable(); // Campo fecha_reintegro como texto
            $table->boolean('permiso')->default(false);
            $table->boolean('no_asitio')->default(false);
            $table->boolean('incapacidad')->default(false);
            $table->boolean('vacaciones')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulacion_novedades');
    }
};
