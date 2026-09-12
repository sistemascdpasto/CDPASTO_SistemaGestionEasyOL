<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('festivos_custom', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('nombre')->default('Festivo personalizado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('festivos_custom');
    }
};
