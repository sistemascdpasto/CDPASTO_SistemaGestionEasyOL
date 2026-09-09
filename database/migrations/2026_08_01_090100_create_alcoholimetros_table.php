<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alcoholimetros', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->date('fecha_calibracion')->nullable();
            $table->date('fecha_vencimiento_certificado')->nullable();
            $table->string('documento_path')->nullable();
            $table->decimal('valor_min', 6, 3)->default(0);
            $table->decimal('valor_max', 6, 3)->default(4);
            $table->enum('estado', ['Disponible', 'En uso', 'En mantenimiento', 'Fuera de servicio'])->default('Disponible');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alcoholimetros');
    }
};
