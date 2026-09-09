<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acis', function (Blueprint $table) {
            $table->id();

            $table->string('folio')->unique();
            $table->date('fecha_alzado')->nullable();
            $table->string('hora_alzado')->nullable();
            $table->date('fecha_incidente')->nullable();
            $table->string('hora_incidente')->nullable();
            $table->string('ubicacion')->nullable();
            $table->string('dentro_fuera_ubicacion')->nullable();
            $table->string('zona')->nullable();
            $table->string('subzona')->nullable();
            $table->string('reporte_de')->nullable();

            $table->string('persona_cometio')->nullable();
            $table->string('persona_cometio_pin')->nullable();
            $table->string('persona_cometio_numero_empleado')->nullable();

            $table->string('reportado_por')->nullable();
            $table->string('qr_reportante')->nullable();
            $table->foreignId('colaborador_id')->nullable()->constrained('colaboradores')->nullOnDelete();
            $table->string('numero_empleado_reporto')->nullable();
            $table->string('pin_reporto')->nullable();

            $table->text('descripcion')->nullable();
            $table->text('posible_solucion')->nullable();
            $table->string('area')->nullable();
            $table->string('subarea')->nullable();
            $table->text('descripcion_ubicacion')->nullable();
            $table->string('clasificacion')->nullable();
            $table->string('tipo_riesgo')->nullable();
            $table->text('descripcion_tipo_riesgo')->nullable();
            $table->string('ciudad_elegida')->nullable();
            $table->string('estado_elegido')->nullable();
            $table->string('nombre_punto_venta')->nullable();
            $table->text('descripcion_punto_venta')->nullable();
            $table->string('tipo_acto_ruta')->nullable();
            $table->boolean('fue_por_externo')->nullable();
            $table->boolean('sif')->nullable();

            $table->string('estatus_asignacion')->nullable();
            $table->dateTime('fecha_hora_asignacion')->nullable();
            $table->string('asignado_por')->nullable();
            $table->string('qr_asignador')->nullable();
            $table->foreignId('asignador_colaborador_id')->nullable()->constrained('colaboradores')->nullOnDelete();
            $table->string('asignado_a')->nullable();
            $table->string('qr_asignado')->nullable();
            $table->foreignId('asignado_colaborador_id')->nullable()->constrained('colaboradores')->nullOnDelete();
            $table->string('cerrado_por')->nullable();
            $table->dateTime('fecha_hora_cierre')->nullable();

            $table->string('bu')->nullable();
            $table->string('compania')->nullable();
            $table->date('fecha_pospuesto')->nullable();

            $table->json('datos_adicionales')->nullable();

            $table->timestamps();

            $table->index('fecha_incidente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acis');
    }
};
