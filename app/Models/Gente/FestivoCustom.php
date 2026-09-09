<?php

namespace App\Models\Gente;

use Illuminate\Database\Eloquent\Model;

class FestivoCustom extends Model
{
    protected $table = 'festivos_custom';

    protected $fillable = [
        'fecha',
        'nombre',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
        ];
    }
}
