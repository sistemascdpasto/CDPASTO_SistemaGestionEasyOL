<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recomendaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('categoria'); // medica | ocupacional | habitos
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recomendaciones');
    }
};
