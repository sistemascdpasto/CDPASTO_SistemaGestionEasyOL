<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PruebaEvidencia extends Model
{
    protected $table = 'prueba_evidencias';

    protected $fillable = [
        'prueba_alcoholemia_id',
        'path',
    ];

    public function prueba(): BelongsTo
    {
        return $this->belongsTo(PruebaAlcoholemia::class, 'prueba_alcoholemia_id');
    }
}
