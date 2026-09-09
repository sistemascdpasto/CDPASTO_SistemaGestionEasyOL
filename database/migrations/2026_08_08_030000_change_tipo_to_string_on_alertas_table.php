<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `tipo` era un enum fijo a nivel de base de datos, lo que obligaba a
     * migrar el esquema cada vez que se agrega un nuevo tipo de alerta (como
     * 'contrato_proximo_vencer' en HU03). Se cambia a string libre, igual al
     * resto de catálogos de este módulo (p. ej. colaboradores.estado_registro),
     * y la lista de tipos válidos queda solo a nivel de aplicación.
     */
    public function up(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->string('tipo', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->enum('tipo', ['prueba_positiva', 'salud_mala', 'no_apto', 'calibracion_proxima', 'certificado_vencido'])->change();
        });
    }
};
