<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega los campos del Paso 1 ("Datos sociodemográficos y laborales") a la
 * tabla encuestas_morbilidad.
 *
 * Todos los campos son nullable para no romper borradores existentes;
 * la obligatoriedad se valida en la capa de aplicación al momento de enviar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encuestas_morbilidad', function (Blueprint $table) {
            // ── Identificación del trabajador ──────────────────────────────
            $table->string('empresa')->nullable()->after('colaborador_id');       // ADENAR / UD / UC
            $table->string('correo_electronico')->nullable()->after('empresa');
            $table->unsignedTinyInteger('edad')->nullable()->after('correo_electronico');
            $table->string('estado_civil')->nullable()->after('edad');

            // ── Familia ────────────────────────────────────────────────────
            $table->string('tiene_hijos')->nullable()->after('estado_civil');     // Si / No
            $table->json('hijos')->nullable()->after('tiene_hijos');              // [{nombre, edad}, ...]
            $table->string('personas_a_cargo')->nullable()->after('hijos');       // Si / No
            $table->json('personas_cargo_detalle')->nullable()->after('personas_a_cargo');
            // [{tipo: 'HIJO'|'PAREJA'|..., edad: int}, ...]

            // ── Educación y vivienda ───────────────────────────────────────
            $table->string('nivel_escolaridad')->nullable()->after('personas_cargo_detalle');
            $table->string('estrato_socioeconomico')->nullable()->after('nivel_escolaridad');
            $table->string('tenencia_vivienda')->nullable()->after('estrato_socioeconomico');

            // ── Residencia ─────────────────────────────────────────────────
            $table->string('ciudad_residencia')->nullable()->after('tenencia_vivienda');
            $table->string('direccion_residencia')->nullable()->after('ciudad_residencia');

            // ── Información laboral ────────────────────────────────────────
            $table->string('tipo_contratacion')->nullable()->after('direccion_residencia');
            $table->string('cargo_paso1')->nullable()->after('tipo_contratacion');
            $table->string('area_paso1')->nullable()->after('cargo_paso1');
            $table->string('antiguedad_empresa')->nullable()->after('area_paso1');
            $table->string('antiguedad_cargo')->nullable()->after('antiguedad_empresa');
            $table->string('duracion_contrato')->nullable()->after('antiguedad_cargo');
            $table->string('turno')->nullable()->after('duracion_contrato');
            $table->string('promedio_ingresos')->nullable()->after('turno');
        });
    }

    public function down(): void
    {
        Schema::table('encuestas_morbilidad', function (Blueprint $table) {
            $table->dropColumn([
                'empresa', 'correo_electronico', 'edad', 'estado_civil',
                'tiene_hijos', 'hijos', 'personas_a_cargo', 'personas_cargo_detalle',
                'nivel_escolaridad', 'estrato_socioeconomico', 'tenencia_vivienda',
                'ciudad_residencia', 'direccion_residencia',
                'tipo_contratacion', 'cargo_paso1', 'area_paso1',
                'antiguedad_empresa', 'antiguedad_cargo', 'duracion_contrato',
                'turno', 'promedio_ingresos',
            ]);
        });
    }
};
