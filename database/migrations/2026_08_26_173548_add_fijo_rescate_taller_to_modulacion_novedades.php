<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modulacion_novedades', function (Blueprint $table) {
            $table->boolean('fijo_rescate')->default(false)->after('fijo');
            $table->boolean('fijo_taller')->default(false)->after('fijo_rescate');
        });
    }

    public function down(): void
    {
        Schema::table('modulacion_novedades', function (Blueprint $table) {
            $table->dropColumn(['fijo_rescate', 'fijo_taller']);
        });
    }
};
