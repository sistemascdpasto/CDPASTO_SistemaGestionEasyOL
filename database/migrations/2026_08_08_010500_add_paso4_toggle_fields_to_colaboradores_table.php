<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->string('es_padrino', 10)->nullable();
            $table->string('tipo_padrino')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn(['es_padrino', 'tipo_padrino']);
        });
    }
};
