<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El selector de "Tipo de prueba" pasa de Entrada/Ruta/Salida a
     * Pre Ruta/Ruta/Post Ruta. `tipo` era un enum fijo a nivel de base de
     * datos (igual que `alertas.tipo` antes de HU03), así que se cambia a
     * string libre y se migran los valores existentes.
     */
    public function up(): void
    {
        Schema::table('pruebas_alcoholemia', function (Blueprint $table) {
            $table->string('tipo', 50)->change();
        });

        DB::table('pruebas_alcoholemia')->where('tipo', 'entrada')->update(['tipo' => 'pre_ruta']);
        DB::table('pruebas_alcoholemia')->where('tipo', 'salida')->update(['tipo' => 'post_ruta']);
    }

    public function down(): void
    {
        DB::table('pruebas_alcoholemia')->where('tipo', 'pre_ruta')->update(['tipo' => 'entrada']);
        DB::table('pruebas_alcoholemia')->where('tipo', 'post_ruta')->update(['tipo' => 'salida']);

        Schema::table('pruebas_alcoholemia', function (Blueprint $table) {
            $table->enum('tipo', ['entrada', 'ruta', 'salida'])->change();
        });
    }
};
