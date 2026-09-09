<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_tripulacion', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->index();
            $table->string('placa')->index();
            $table->string('documento')->nullable();
            $table->string('nombre');
            $table->string('cargo')->nullable();
            $table->integer('total_eventos')->default(0);
            $table->timestamps();
            $table->index(['fecha', 'placa']);
            $table->unique(['fecha', 'placa', 'documento'], 'unique_evento_tripulacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_tripulacion');
    }
};
