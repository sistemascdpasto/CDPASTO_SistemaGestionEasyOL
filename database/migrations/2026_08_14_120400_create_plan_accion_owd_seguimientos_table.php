<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_accion_owd_seguimientos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_accion_owd_id')->constrained('plan_accion_owd')->cascadeOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('estado');
            $table->text('observacion')->nullable();
            $table->date('fecha');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_accion_owd_seguimientos');
    }
};
