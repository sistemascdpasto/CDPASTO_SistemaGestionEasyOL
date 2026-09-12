<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pruebas_alcoholemia', function (Blueprint $table) {
            $table->string('firma_path')->nullable()->after('evidencia_path');
            $table->string('qr_token')->nullable()->unique()->after('firma_path');
            $table->dateTime('recordatorio_enviado_at')->nullable()->after('programada_en');
        });
    }

    public function down(): void
    {
        Schema::table('pruebas_alcoholemia', function (Blueprint $table) {
            $table->dropColumn(['firma_path', 'qr_token', 'recordatorio_enviado_at']);
        });
    }
};
