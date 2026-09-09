<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;

class EncuestaMorbilidadPregunta extends Model
{
    protected $table = 'encuesta_morbilidad_preguntas';

    protected $fillable = [
        'numero_pregunta',
        'seccion_numero',
        'seccion_titulo',
        'texto',
        'tipo',
        'obligatorio',
        'opciones',
        'con_otro',
        'segmento',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'numero_pregunta' => 'integer',
            'seccion_numero'  => 'integer',
            'obligatorio'     => 'boolean',
            'opciones'        => 'array',
            'con_otro'        => 'boolean',
            'orden'           => 'integer',
            'activo'          => 'boolean',
        ];
    }
}
