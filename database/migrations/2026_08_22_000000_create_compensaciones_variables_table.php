<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compensaciones_variables', function (Blueprint $table) {
            $table->id();
            $table->integer('anio')->nullable();
            $table->string('mes')->nullable();
            $table->string('mes2')->nullable();
            $table->string('regional')->nullable();
            $table->string('cd')->nullable();
            $table->string('codigo_ob')->nullable();
            $table->string('codigo_gp')->nullable();
            $table->string('identificador')->nullable();
            $table->string('nombre')->nullable();
            $table->string('cargo')->nullable();
            $table->decimal('ausencia_justificada', 8, 2)->default(0);
            $table->decimal('ausencia_injustificada', 8, 2)->default(0);
            $table->decimal('tri_fatalidades', 8, 2)->default(0);
            $table->string('adherencia_gp')->nullable();
            $table->string('market_refusals')->nullable();
            $table->decimal('porcentaje_rechazos', 8, 2)->default(0);
            $table->decimal('habilitadores', 8, 2)->default(1);
            $table->string('variable')->nullable();
            $table->decimal('dias_trabajados', 8, 2)->default(0);
            $table->decimal('salario_variable', 15, 2)->default(0);
            $table->decimal('pago_variable_dt', 15, 2)->default(0);
            $table->decimal('total_pago', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compensaciones_variables');
    }
};
