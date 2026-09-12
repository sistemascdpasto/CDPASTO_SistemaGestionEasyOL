<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['prueba_positiva', 'salud_mala', 'no_apto', 'calibracion_proxima', 'certificado_vencido']);
            $table->foreignId('colaborador_id')->nullable()->constrained('colaboradores')->nullOnDelete();
            $table->foreignId('alcoholimetro_id')->nullable()->constrained('alcoholimetros')->nullOnDelete();
            $table->foreignId('prueba_alcoholemia_id')->nullable()->constrained('pruebas_alcoholemia')->nullOnDelete();
            $table->string('mensaje');
            $table->boolean('atendida')->default(false);
            $table->foreignId('atendida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('atendida_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
