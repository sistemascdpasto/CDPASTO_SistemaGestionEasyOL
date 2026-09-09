<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulaciones', function (Blueprint $table) {
            $table->id();
            $table->string('fecha'); // Campo fecha como texto
            $table->string('ud_programado_por')->nullable();
            $table->foreignId('despachado_por_colaborador_id')->nullable()->constrained('colaboradores')->nullOnDelete();
            $table->string('despachado_por_nombre')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulaciones');
    }
};
