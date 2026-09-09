<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar duplicados antes de crear el índice único:
        // Conserva el registro con el id más alto para cada par (cedula, fecha).
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("
                DELETE t1
                FROM compensaciones_variables_diarias t1
                INNER JOIN compensaciones_variables_diarias t2
                ON  t1.cedula = t2.cedula
                AND t1.fecha  = t2.fecha
                AND t1.id     < t2.id
            ");
        }

        Schema::table('compensaciones_variables_diarias', function (Blueprint $table) {
            // Eliminar el índice simple previo en (cedula, fecha)
            $table->dropIndex(['cedula', 'fecha']);
            // Crear el índice UNIQUE que habilita upsert() eficiente
            $table->unique(['cedula', 'fecha'], 'cvd_cedula_fecha_unique');
        });
    }

    public function down(): void
    {
        Schema::table('compensaciones_variables_diarias', function (Blueprint $table) {
            $table->dropUnique('cvd_cedula_fecha_unique');
            $table->index(['cedula', 'fecha']);
        });
    }
};
