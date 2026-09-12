<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El Excel de eventos de tripulación no siempre incluye la columna "nombre".
     * La hacemos nullable para que el insert no falle cuando viene vacía.
     */
    public function up(): void
    {
        Schema::table('eventos_tripulacion', function (Blueprint $table) {
            $table->string('nombre')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('eventos_tripulacion', function (Blueprint $table) {
            $table->string('nombre')->nullable(false)->change();
        });
    }
};
