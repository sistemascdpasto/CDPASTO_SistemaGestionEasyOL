<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluacionRecomendacion extends Model
{
    protected $table = 'evaluacion_recomendaciones';

    protected $fillable = [
        'evaluacion_medica_id',
        'recomendacion_id',
        'observacion',
        'soporte_path',
        'activa',
        'origen',
        'fecha_registro',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'fecha_registro' => 'date:Y-m-d',
        ];
    }

    public function evaluacionMedica(): BelongsTo
    {
        return $this->belongsTo(EvaluacionMedica::class);
    }

    public function recomendacion(): BelongsTo
    {
        return $this->belongsTo(Recomendacion::class);
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(RecomendacionSeguimiento::class);
    }
}
