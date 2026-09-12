<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;

class EncuestaMorbilidadSeccion extends Model
{
    protected $table = 'encuesta_morbilidad_secciones';

    protected $fillable = [
        'numero',
        'titulo',
        'descripcion',
        'imagen_portada',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * URL pública de la imagen de portada, o null si no tiene.
     */
    public function getImagenPortadaUrlAttribute(): ?string
    {
        return $this->imagen_portada
            ? asset('storage/' . $this->imagen_portada)
            : null;
    }
}
