<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alertas_velocidad_curva', function (Blueprint $table) {
            $table->string('hora', 20)->nullable()->after('fecha');
            $table->string('regional', 100)->nullable()->after('hora');
            $table->string('cd', 100)->nullable()->after('regional');
        });
    }

    public function down(): void
    {
        Schema::table('alertas_velocidad_curva', function (Blueprint $table) {
            $table->dropColumn(['hora', 'regional', 'cd']);
        });
    }
};
