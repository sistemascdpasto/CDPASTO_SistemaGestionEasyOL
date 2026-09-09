<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compensaciones_variables_diarias', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->nullable();
            $table->integer('anio')->nullable();
            $table->string('mes')->nullable();
            $table->string('placa')->nullable();
            $table->string('transporte')->nullable();
            $table->string('rr')->nullable();
            $table->string('cedula')->nullable();
            $table->string('nombre_completo')->nullable();
            $table->string('cargo')->nullable();
            $table->decimal('rechazos', 12, 2)->default(0);
            $table->decimal('cal_rechazos', 15, 2)->default(0);
            $table->decimal('cal_rechazos_2', 15, 2)->default(0);
            $table->decimal('valor_x_dia', 15, 2)->default(0);
            $table->decimal('valor_var', 15, 2)->default(0);
            $table->decimal('valor_perdido', 15, 2)->default(0);
            $table->string('porcentaje_variable')->nullable();
            $table->string('porcentaje_variable_no_cum')->nullable();
            $table->decimal('meta_1', 12, 2)->default(0);
            $table->decimal('meta_2', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['cedula', 'fecha']);
            $table->index(['anio', 'mes']);
            $table->index('placa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compensaciones_variables_diarias');
    }
};
