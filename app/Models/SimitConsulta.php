<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimitConsulta extends Model
{
    protected $fillable = [
        'placa',
        'fecha_hora',
        'status',
        'raw_text',
        'screenshot_nombre',
        'screenshot',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    // El screenshot puede pesar cientos de KB; nunca se serializa por
    // accidente en una respuesta JSON que no lo pida explicitamente.
    protected $hidden = [
        'screenshot',
    ];
}
