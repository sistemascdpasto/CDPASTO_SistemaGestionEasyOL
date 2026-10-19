<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contratistas, visitantes y demás personal externo que ingresa a las
     * instalaciones de CD Nariño. No son colaboradores (no tienen hoja de vida
     * en Gente), pero también deben quedar con trazabilidad de la prueba de
     * alcoholemia. Se identifican por documento para reutilizar sus datos y su
     * última firma en registros posteriores.
     */
    public function up(): void
    {
        Schema::create('personas_externas', function (Blueprint $table) {
            $table->id();
            $table->string('documento', 30)->unique();
            $table->string('nombres');
            $table->string('apellidos')->nullable();
            $table->string('empresa');
            $table->string('tipo_vinculo', 30);
            $table->string('cargo')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->timestamps();
        });

        Schema::table('pruebas_alcoholemia', function (Blueprint $table) {
            $table->foreignId('colaborador_id')->nullable()->change();
            $table->foreignId('persona_externa_id')->nullable()->after('colaborador_id')->constrained('personas_externas');
        });
    }

    public function down(): void
    {
        Schema::table('pruebas_alcoholemia', function (Blueprint $table) {
            $table->dropConstrainedForeignId('persona_externa_id');
        });

        Schema::dropIfExists('personas_externas');
    }
};
