<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incentivos', function (Blueprint $table) {
            $table->decimal('valor_indicador_1', 10, 4)->nullable()->after('podium');
            $table->decimal('valor_indicador_2', 10, 4)->nullable()->after('valor_indicador_1');
            $table->decimal('valor_indicador_3', 10, 4)->nullable()->after('valor_indicador_2');
            $table->decimal('total_4', 10, 4)->nullable()->after('valor_indicador_3');
            $table->decimal('meta_4',  10, 4)->nullable()->after('total_4');
        });
    }

    public function down(): void
    {
        Schema::table('incentivos', function (Blueprint $table) {
            $table->dropColumn(['valor_indicador_1', 'valor_indicador_2', 'valor_indicador_3', 'total_4', 'meta_4']);
        });
    }
};
