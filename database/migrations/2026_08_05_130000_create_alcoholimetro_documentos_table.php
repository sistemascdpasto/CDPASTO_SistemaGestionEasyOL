<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alcoholimetro_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alcoholimetro_id')
                ->constrained('alcoholimetros')
                ->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();

            $table->index('alcoholimetro_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alcoholimetro_documentos');
    }
};
