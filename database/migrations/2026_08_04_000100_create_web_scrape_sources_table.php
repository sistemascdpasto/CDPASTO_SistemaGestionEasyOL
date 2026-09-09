<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_scrape_sources', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_fuente');
            $table->string('url');
            $table->string('selector_css');
            $table->string('categoria');
            $table->boolean('activo')->default(true);
            $table->timestamp('ultimo_scrape')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_scrape_sources');
    }
};
