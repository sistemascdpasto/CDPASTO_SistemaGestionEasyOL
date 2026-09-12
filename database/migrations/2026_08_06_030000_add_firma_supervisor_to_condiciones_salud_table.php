<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('condiciones_salud', function (Blueprint $table) {
            $table->string('firma_supervisor_path')->nullable();
            $table->foreignId('firmado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('firmado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('condiciones_salud', function (Blueprint $table) {
            $table->dropConstrainedForeignId('firmado_por_id');
            $table->dropColumn(['firma_supervisor_path', 'firmado_en']);
        });
    }
};
