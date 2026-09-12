<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dpo_academy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')
                ->nullable()
                ->constrained('colaboradores')
                ->nullOnDelete();
            
            $table->string('region')->nullable();
            $table->string('centro')->nullable();
            $table->string('negocio')->nullable();
            $table->string('qr_safety')->nullable();
            $table->string('nombre');
            $table->string('cargo')->nullable();
            $table->string('coronita')->nullable();
            $table->decimal('calificacion', 8, 2)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();

            $table->index(['nombre', 'qr_safety']);
            $table->index('region');
            $table->index('centro');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dpo_academy');
    }
};
