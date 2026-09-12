<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colaborador_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')
                ->constrained('colaboradores')
                ->cascadeOnDelete();
            $table->string('campo', 100);
            $table->string('path');
            $table->timestamps();

            $table->index(['colaborador_id', 'campo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaborador_documentos');
    }
};
