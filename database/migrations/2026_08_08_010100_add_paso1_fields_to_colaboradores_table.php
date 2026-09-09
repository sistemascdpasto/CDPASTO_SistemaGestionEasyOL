<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            // Paso 1 — tipo de documento de identificación
            $table->string('tipo_documento', 30)->nullable();
            $table->string('tipo_documento_otro_label')->nullable();

            // Paso 1 — condiciones particulares (detalle)
            $table->string('discapacidad_tipo')->nullable();
            $table->text('discapacidad_observaciones')->nullable();
            $table->text('victima_conflicto_observaciones')->nullable();

            // Paso 1 — libreta militar y RUNT quedan como toggle; el archivo vive en colaborador_documentos
            $table->string('runt_aplica', 10)->nullable();

            // Paso 1 — seguridad social
            $table->string('eps')->nullable();
            $table->string('eps_otro')->nullable();
            $table->string('afp')->nullable();
            $table->string('afp_otro')->nullable();
            $table->string('arl')->default('ARL SURA');
            $table->string('arl_otro')->nullable();

            // Paso 1 — información aprendiz SENA (condicional a Cargo = Aprendiz SENA)
            $table->string('sena_especialidad')->nullable();
            $table->unsignedInteger('sena_numero_grupo')->nullable();
            $table->string('sena_institucion')->nullable();
            $table->string('sena_nit', 30)->nullable();
            $table->string('sena_centro_formacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_documento',
                'tipo_documento_otro_label',
                'discapacidad_tipo',
                'discapacidad_observaciones',
                'victima_conflicto_observaciones',
                'runt_aplica',
                'eps',
                'eps_otro',
                'afp',
                'afp_otro',
                'arl',
                'arl_otro',
                'sena_especialidad',
                'sena_numero_grupo',
                'sena_institucion',
                'sena_nit',
                'sena_centro_formacion',
            ]);
        });
    }
};
