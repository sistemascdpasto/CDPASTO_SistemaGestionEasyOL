<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alcoholimetro_mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alcoholimetro_id')->constrained('alcoholimetros')->cascadeOnDelete();
            $table->date('fecha');
            $table->text('descripcion');
            $table->foreignId('realizado_por')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alcoholimetro_mantenimientos');
    }
};
