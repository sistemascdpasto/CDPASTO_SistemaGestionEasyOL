<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculo_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')
                ->constrained('vehiculos')
                ->cascadeOnDelete();
            $table->string('campo', 100);
            $table->string('path');
            $table->date('fecha_documento')->nullable();
            $table->timestamps();

            $table->index(['vehiculo_id', 'campo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_documentos');
    }
};
