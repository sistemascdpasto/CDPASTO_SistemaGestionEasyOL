<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('simit_consultas', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 20);
            $table->dateTime('fecha_hora');
            $table->string('status', 30);
            $table->text('raw_text')->nullable();
            $table->string('screenshot_nombre')->nullable();
            $table->timestamps();

            $table->index(['placa', 'fecha_hora']);
        });

        // El BLOB por defecto de MySQL solo admite 64KB, insuficiente para
        // un pantallazo PNG, asi que se agrega como MEDIUMBLOB (16MB) con
        // SQL crudo. En otros drivers (sqlite en tests) el binary() de
        // Blueprint ya alcanza sin ese limite.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE simit_consultas ADD screenshot MEDIUMBLOB NULL AFTER screenshot_nombre');
        } else {
            Schema::table('simit_consultas', function (Blueprint $table) {
                $table->binary('screenshot')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simit_consultas');
    }
};
