<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('condiciones_salud', function (Blueprint $table) {
            $table->foreignId('prueba_alcoholemia_id')->nullable()->after('colaborador_id')
                ->constrained('pruebas_alcoholemia')->nullOnDelete();
            $table->boolean('consentimiento_aceptado')->default(false);
            $table->dateTime('consentimiento_en')->nullable();
            $table->string('consentimiento_ip')->nullable();
            $table->string('consentimiento_texto_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('condiciones_salud', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prueba_alcoholemia_id');
            $table->dropColumn(['consentimiento_aceptado', 'consentimiento_en', 'consentimiento_ip', 'consentimiento_texto_version']);
        });
    }
};
