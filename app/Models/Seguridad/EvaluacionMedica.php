<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluacionMedica extends Model
{
    protected $table = 'evaluaciones_medicas';

    protected $fillable = [
        'colaborador_id',
        'tipo_evaluacion',
        'numero_periodo',
        'fecha_evaluacion',
        'proximo_examen_fecha',
        'fecha_limite',
        'fecha_entrada_bandeja',
        'concepto_aptitud_id',
        'emite',
        'fecha_programacion',
        'fecha_examen_ejecutado',
        'soporte_path',
        'seguimiento_recomendaciones',
        'seguimiento_recomendaciones_detalle',
        'estado_seguimiento',
        'empresa',
        'carta_entregada',
        'carta_entregada_observacion',
        'estado',
        'tipo_cierre',
        'fecha_rechazo',
        'observacion_rechazo',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_evaluacion' => 'date:Y-m-d',
            'proximo_examen_fecha' => 'date:Y-m-d',
            'fecha_limite' => 'date:Y-m-d',
            'fecha_entrada_bandeja' => 'date:Y-m-d',
            'fecha_programacion' => 'date:Y-m-d',
            'fecha_examen_ejecutado' => 'date:Y-m-d',
            'fecha_rechazo' => 'date:Y-m-d',
            'carta_entregada' => 'boolean',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function conceptoAptitud(): BelongsTo
    {
        return $this->belongsTo(ConceptoAptitud::class);
    }

    public function examenes(): HasMany
    {
        return $this->hasMany(ExamenEvaluacion::class);
    }

    public function recomendaciones(): HasMany
    {
        return $this->hasMany(EvaluacionRecomendacion::class);
    }
}
