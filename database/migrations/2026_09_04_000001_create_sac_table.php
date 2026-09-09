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
        Schema::create('sac', function (Blueprint $table) {
            $table->id();
            $table->string('anio')->nullable();
            $table->string('numero_caso_estandar')->nullable();
            $table->string('nombre_cuenta')->nullable();
            $table->string('nombre_contacto')->nullable();
            $table->date('fecha')->nullable();
            $table->text('descripcion')->nullable();
            $table->date('fecha_resuelto')->nullable();
            $table->text('comentario')->nullable();
            $table->string('aplica')->nullable();
            $table->string('mes')->nullable();
            $table->string('subcategoria')->nullable();
            $table->string('motivo_queja')->nullable();
            $table->string('placa')->nullable();
            $table->string('responsable')->nullable();
            $table->foreignId('colaborador_id')
                ->nullable()
                ->constrained('colaboradores')
                ->nullOnDelete();
            $table->string('documento_transporte')->nullable();
            $table->text('plan_accion')->nullable();
            $table->string('tiempo_cierre_caso')->nullable();
            $table->string('porcentaje_si_no')->nullable();
            $table->string('cumplimiento_cierre')->nullable();
            $table->string('ytd')->nullable();
            $table->string('hora')->nullable();
            $table->timestamps();

            // Indexes for fast filtering
            $table->index('anio');
            $table->index('mes');
            $table->index('fecha');
            $table->index('responsable');
            $table->index('colaborador_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sac');
    }
};
