<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncuestaMorbilidadRespuesta extends Model
{
    protected $table = 'encuesta_morbilidad_respuestas';

    protected $fillable = [
        'encuesta_morbilidad_id',
        'numero_pregunta',
        'valor',
        'detalle',
    ];

    protected function casts(): array
    {
        return [
            'numero_pregunta' => 'integer',
        ];
    }

    public function encuestaMorbilidad(): BelongsTo
    {
        return $this->belongsTo(EncuestaMorbilidad::class);
    }
}
