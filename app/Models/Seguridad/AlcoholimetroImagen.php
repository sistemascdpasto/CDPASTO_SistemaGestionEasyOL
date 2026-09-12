<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlcoholimetroImagen extends Model
{
    protected $table = 'alcoholimetro_imagenes';

    protected $fillable = [
        'alcoholimetro_id',
        'path',
    ];

    public function alcoholimetro(): BelongsTo
    {
        return $this->belongsTo(Alcoholimetro::class);
    }
}
