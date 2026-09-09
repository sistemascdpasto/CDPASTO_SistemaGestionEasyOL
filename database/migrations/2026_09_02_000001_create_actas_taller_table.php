<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actas_taller', function (Blueprint $table) {
            $table->id();

            // Número de acta correlativo: AT-000001
            $table->string('numero_acta', 20)->unique();

            // Vehículo
            $table->string('placa', 50);

            // Fechas
            $table->dateTime('fecha_entrega');
            $table->dateTime('hora_entrega')->nullable();  // alias de fecha_entrega con hora
            $table->dateTime('fecha_estimada_solucion')->nullable();
            $table->dateTime('fecha_cierre')->nullable();

            // Kilometraje
            $table->unsignedBigInteger('kilometraje_entrada')->nullable();
            $table->unsignedBigInteger('kilometraje_salida')->nullable();

            // Taller
            $table->string('taller', 150)->nullable();

            // Motivo de ingreso
            $table->string('motivo_ingreso', 255)->nullable();

            // Quien reporta (colaborador)
            $table->foreignId('colaborador_id')->nullable()->constrained('colaboradores')->nullOnDelete();
            $table->string('quien_reporta', 150)->nullable(); // nombre texto libre como fallback

            // Diagnóstico y solución del taller
            $table->text('diagnostico_taller')->nullable();
            $table->text('solucion_realizada')->nullable();

            // Estado general del vehículo (JSON: {carroceria, llantas, luces, mecanica, interior, vidrios, tablero})
            $table->json('estado_vehiculo')->nullable();

            // Inventario / accesorios (JSON: array de strings marcados)
            $table->json('inventario')->nullable();

            // Observaciones generales
            $table->text('observaciones')->nullable();
            $table->text('observacion_cierre')->nullable();

            // Combustible %
            $table->unsignedTinyInteger('combustible')->nullable();

            // Firmas (paths de imágenes en storage)
            $table->string('firma_entrega')->nullable();
            $table->string('firma_recibe')->nullable();
            $table->string('firma_autorizacion')->nullable();

            // Datos de firmas (nombre + cargo)
            $table->string('nombre_entrega', 120)->nullable();
            $table->string('cargo_entrega', 100)->nullable();
            $table->string('nombre_recibe', 120)->nullable();
            $table->string('cargo_recibe', 100)->nullable();
            $table->string('nombre_autorizacion', 120)->nullable();
            $table->string('cargo_autorizacion', 100)->nullable();

            // Estado general del acta
            $table->string('estado_acta', 30)->default('en_taller');
            // en_taller | cerrada | cancelada

            // Quien creó el acta
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('placa');
            $table->index('estado_acta');
            $table->index('fecha_entrega');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actas_taller');
    }
};
