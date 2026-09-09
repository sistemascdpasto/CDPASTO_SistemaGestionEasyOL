<?php

namespace App\Models\Seguridad;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecomendacionSeguimiento extends Model
{
    protected $table = 'recomendacion_seguimientos';

    protected $fillable = [
        'evaluacion_recomendacion_id',
        'fecha_seguimiento',
        'estado_seguimiento',
        'observacion',
        'responsable_id',
        'fecha_proximo_seguimiento',
        'carta_recomendacion_entregada',
        'soporte_path',
    ];

    protected function casts(): array
    {
        return [
            'fecha_seguimiento' => 'date:Y-m-d',
            'fecha_proximo_seguimiento' => 'date:Y-m-d',
            'carta_recomendacion_entregada' => 'boolean',
        ];
    }

    public function evaluacionRecomendacion(): BelongsTo
    {
        return $this->belongsTo(EvaluacionRecomendacion::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
