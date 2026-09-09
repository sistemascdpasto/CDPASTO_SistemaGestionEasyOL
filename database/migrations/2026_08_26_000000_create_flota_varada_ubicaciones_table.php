<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flota_varada_ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->string('lugar')->unique();
            $table->decimal('latitud', 9, 6);
            $table->decimal('longitud', 9, 6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flota_varada_ubicaciones');
    }
};
