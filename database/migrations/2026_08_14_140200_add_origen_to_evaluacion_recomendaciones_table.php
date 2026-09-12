<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluacion_recomendaciones', function (Blueprint $table) {
            // HU-033 CA-033.2: distingue si la recomendación se marcó por
            // coincidencia automática contra el PDF o manualmente por SST.
            $table->string('origen')->default('manual')->after('activa');
        });
    }

    public function down(): void
    {
        Schema::table('evaluacion_recomendaciones', function (Blueprint $table) {
            $table->dropColumn('origen');
        });
    }
};
