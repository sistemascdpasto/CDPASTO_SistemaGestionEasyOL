<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebScrapingSource extends Model
{
    use SoftDeletes;

    protected $table = 'web_scrape_sources';

    protected $fillable = [
        'nombre_fuente',
        'url',
        'selector_css',
        'categoria',
        'activo',
        'ultimo_scrape',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ultimo_scrape' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
