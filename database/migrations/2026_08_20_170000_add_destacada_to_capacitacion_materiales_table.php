<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capacitacion_materiales', function (Blueprint $table) {
            $table->boolean('destacada')->default(false)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('capacitacion_materiales', function (Blueprint $table) {
            $table->dropColumn('destacada');
        });
    }
};
