<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluacion_recomendaciones', function (Blueprint $table) {
            $table->string('soporte_path')->nullable()->after('observacion');
        });
    }

    public function down(): void
    {
        Schema::table('evaluacion_recomendaciones', function (Blueprint $table) {
            $table->dropColumn('soporte_path');
        });
    }
};
