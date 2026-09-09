<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulacion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modulacion_id')->constrained('modulaciones')->cascadeOnDelete();
            $table->string('placa');
            $table->string('doc_tras')->nullable();
            $table->string('ud')->nullable();
            $table->string('cargo')->nullable();
            $table->foreignId('colaborador_id')->nullable()->constrained('colaboradores')->nullOnDelete();
            $table->string('cedula')->nullable();
            $table->string('nombres')->nullable();
            $table->string('reunion')->nullable(); // Campo reunión de texto
            $table->json('tripulacion')->nullable(); // Lista de colaboradores por placa (tripulacion)
            $table->json('viajes')->nullable(); // Lista de viajes {lugares, cliente, peso}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulacion_items');
    }
};
