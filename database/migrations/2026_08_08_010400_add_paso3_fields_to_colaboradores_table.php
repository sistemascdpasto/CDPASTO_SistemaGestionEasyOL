<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->string('centro')->nullable();
            $table->string('centro_trabajo')->nullable();
            $table->date('fecha_ingreso_empresa')->nullable();
            $table->date('fecha_retiro_empresa')->nullable();
            $table->string('motivo_retiro')->nullable();
            $table->string('tipo_contrato')->nullable();
            $table->date('contrato_fecha_desde')->nullable();
            $table->date('contrato_fecha_hasta')->nullable();
            $table->string('vacaciones_aplica', 10)->nullable();
            $table->date('vacaciones_fecha_desde')->nullable();
            $table->date('vacaciones_fecha_hasta')->nullable();
            $table->date('vacaciones_pagadas_fecha_desde')->nullable();
            $table->date('vacaciones_pagadas_fecha_hasta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn([
                'centro',
                'centro_trabajo',
                'fecha_ingreso_empresa',
                'fecha_retiro_empresa',
                'motivo_retiro',
                'tipo_contrato',
                'contrato_fecha_desde',
                'contrato_fecha_hasta',
                'vacaciones_aplica',
                'vacaciones_fecha_desde',
                'vacaciones_fecha_hasta',
                'vacaciones_pagadas_fecha_desde',
                'vacaciones_pagadas_fecha_hasta',
            ]);
        });
    }
};
