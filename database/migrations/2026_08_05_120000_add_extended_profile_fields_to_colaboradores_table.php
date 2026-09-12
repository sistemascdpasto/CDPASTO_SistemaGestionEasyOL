<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->string('expedido_en')->nullable();
            $table->string('sexo', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('ciudad_residencia')->nullable();
            $table->string('direccion')->nullable();
            $table->string('estrato', 10)->nullable();
            $table->string('celular_1', 30)->nullable();
            $table->string('celular_2', 30)->nullable();
            $table->string('correo')->nullable();
            $table->string('estado_civil', 30)->nullable();
            $table->string('discapacidad', 20)->nullable();
            $table->string('victima_conflicto', 10)->nullable();
            $table->string('libreta_militar', 20)->nullable();
            $table->string('ha_trabajado_antes', 10)->nullable();
            $table->string('cargo_anterior')->nullable();
            $table->date('fecha_ultima_laboral')->nullable();
            $table->string('tiene_experiencia', 10)->nullable();
            $table->string('area_experiencia')->nullable();
            $table->string('cargo_experiencia')->nullable();
            $table->unsignedTinyInteger('anios_experiencia')->nullable();
            $table->string('codigo_qr_skap', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn([
                'expedido_en',
                'sexo',
                'fecha_nacimiento',
                'ciudad_residencia',
                'direccion',
                'estrato',
                'celular_1',
                'celular_2',
                'correo',
                'estado_civil',
                'discapacidad',
                'victima_conflicto',
                'libreta_militar',
                'ha_trabajado_antes',
                'cargo_anterior',
                'fecha_ultima_laboral',
                'tiene_experiencia',
                'area_experiencia',
                'cargo_experiencia',
                'anios_experiencia',
                'codigo_qr_skap',
            ]);
        });
    }
};
