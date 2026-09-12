<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flota_varadas', function (Blueprint $table) {
            $table->id();
            $table->string('placa');
            $table->dateTime('fecha_reportada');
            $table->dateTime('fecha_asistencia')->nullable();
            $table->dateTime('fecha_solucion')->nullable();
            $table->string('sistema')->nullable();
            $table->string('tipo_falla')->nullable();
            $table->text('descripcion')->nullable();
            $table->text('causa_probable')->nullable();
            $table->boolean('repetitiva')->default(false);
            $table->string('ruta')->nullable();
            $table->string('lugar')->nullable();
            $table->string('proveedor')->nullable();
            $table->string('tipo_solucion')->nullable();
            $table->string('impacto')->nullable();
            $table->unsignedTinyInteger('gravedad')->nullable();
            $table->text('observaciones')->nullable();
            $table->decimal('latitud', 9, 6)->nullable();
            $table->decimal('longitud', 9, 6)->nullable();
            $table->string('origen')->default('manual');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['placa', 'fecha_reportada']);
            $table->index('sistema');
            $table->index('ruta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flota_varadas');
    }
};
