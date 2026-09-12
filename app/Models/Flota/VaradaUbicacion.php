<?php

namespace App\Models\Flota;

use Illuminate\Database\Eloquent\Model;

class VaradaUbicacion extends Model
{
    protected $table = 'flota_varada_ubicaciones';

    protected $fillable = [
        'lugar',
        'latitud',
        'longitud',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'decimal:6',
            'longitud' => 'decimal:6',
        ];
    }
}
