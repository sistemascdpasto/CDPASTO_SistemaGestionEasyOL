<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cambia 'rechazos' de unsignedSmallInteger a decimal(6,2)
     * para almacenar el porcentaje tal como viene del Excel (ej: 3.50).
     */
    public function up(): void
    {
        Schema::table('eventos_tripulacion', function (Blueprint $table) {
            $table->decimal('rechazos', 6, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('eventos_tripulacion', function (Blueprint $table) {
            $table->unsignedSmallInteger('rechazos')->nullable()->change();
        });
    }
};
