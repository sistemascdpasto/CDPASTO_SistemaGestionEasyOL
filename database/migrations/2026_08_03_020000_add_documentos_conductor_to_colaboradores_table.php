<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->string('documento_licencia_conduccion')->nullable()->after('documento_cedula');
            $table->string('documento_carnet_manejo_defensivo')->nullable()->after('documento_licencia_conduccion');
            $table->string('documento_certificado_manejo_defensivo')->nullable()->after('documento_carnet_manejo_defensivo');
            $table->string('documento_carnet_ingreso_cd')->nullable()->after('documento_certificado_manejo_defensivo');
            $table->string('documento_simit')->nullable()->after('documento_carnet_ingreso_cd');
            $table->string('documento_examen_medico_ocupacional')->nullable()->after('documento_simit');
            $table->string('documento_recordatorio_vehiculo_licencia_conduccion')->nullable()->after('documento_examen_medico_ocupacional');
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn([
                'documento_licencia_conduccion',
                'documento_carnet_manejo_defensivo',
                'documento_certificado_manejo_defensivo',
                'documento_carnet_ingreso_cd',
                'documento_simit',
                'documento_examen_medico_ocupacional',
                'documento_recordatorio_vehiculo_licencia_conduccion',
            ]);
        });
    }
};
