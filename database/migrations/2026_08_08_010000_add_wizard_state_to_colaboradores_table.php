<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->string('estado_registro', 20)->default('completo')->after('is_active');
            $table->unsignedTinyInteger('wizard_step')->nullable()->after('estado_registro');

            $table->index('estado_registro');
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropIndex(['estado_registro']);
            $table->dropColumn(['estado_registro', 'wizard_step']);
        });
    }
};
