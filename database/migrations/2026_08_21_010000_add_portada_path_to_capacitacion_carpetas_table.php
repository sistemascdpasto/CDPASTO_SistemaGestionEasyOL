<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capacitacion_carpetas', function (Blueprint $table) {
            $table->string('portada_path', 1024)->nullable()->after('icono');
        });
    }

    public function down(): void
    {
        Schema::table('capacitacion_carpetas', function (Blueprint $table) {
            $table->dropColumn('portada_path');
        });
    }
};
