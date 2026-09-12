<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actas_taller', function (Blueprint $table) {
            $table->string('identificacion_entrega', 30)->nullable()->after('cargo_entrega');
            $table->string('telefono_entrega', 30)->nullable()->after('identificacion_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('actas_taller', function (Blueprint $table) {
            $table->dropColumn(['identificacion_entrega', 'telefono_entrega']);
        });
    }
};
