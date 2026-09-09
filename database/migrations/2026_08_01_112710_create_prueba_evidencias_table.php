<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prueba_evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prueba_alcoholemia_id')->constrained('pruebas_alcoholemia')->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prueba_evidencias');
    }
};
