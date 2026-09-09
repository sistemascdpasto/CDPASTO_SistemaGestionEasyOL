<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EvaluacionOwdPregunta extends Model
{
    protected $table = 'evaluacion_owd_preguntas';

    protected $fillable = [
        'evaluacion_owd_id',
        'proceso',
        'actividad',
        'tarea',
        'descripcion',
        'puntuacion',
        'ponderacion',
        'requiere_plan_accion',
        'version',
        'datos_adicionales',
    ];

    protected function casts(): array
    {
        return [
            'ponderacion' => 'decimal:2',
            'requiere_plan_accion' => 'boolean',
            'datos_adicionales' => 'array',
        ];
    }

    public function evaluacionOwd(): BelongsTo
    {
        return $this->belongsTo(EvaluacionOwd::class);
    }

    public function planAccion(): HasOne
    {
        return $this->hasOne(PlanAccionOwd::class);
    }
}
