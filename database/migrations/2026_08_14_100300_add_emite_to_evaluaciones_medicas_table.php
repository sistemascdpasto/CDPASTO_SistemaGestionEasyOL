<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluaciones_medicas', function (Blueprint $table) {
            $table->string('emite')->nullable()->after('concepto_aptitud_id');
        });
    }

    public function down(): void
    {
        Schema::table('evaluaciones_medicas', function (Blueprint $table) {
            $table->dropColumn('emite');
        });
    }
};
