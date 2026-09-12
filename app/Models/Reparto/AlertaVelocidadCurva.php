<?php

namespace App\Models\Reparto;

use Illuminate\Database\Eloquent\Model;

class AlertaVelocidadCurva extends Model
{
    protected $table = 'alertas_velocidad_curva';

    protected $fillable = [
        'fecha',
        'hora',
        'regional',
        'cd',
        'nombre',
        'alerta',
        'velocidad',
        'coordenada',
        'cantidad_eventos',
        'mes',
        'hash',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'velocidad' => 'float',
            'cantidad_eventos' => 'integer',
            'mes' => 'integer',
        ];
    }
}
