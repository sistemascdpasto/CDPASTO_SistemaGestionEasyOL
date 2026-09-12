<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamenEvaluacion extends Model
{
    protected $table = 'examenes_evaluacion';

    protected $fillable = [
        'evaluacion_medica_id',
        'examen_id',
        'obligatorio',
        'origen',
        'estado',
        'fecha_programacion',
        'fecha_ejecucion',
        'soporte_path',
        'fecha_ingreso_pdf',
        'hora_ingreso_pdf',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'obligatorio' => 'boolean',
            'fecha_programacion' => 'date:Y-m-d',
            'fecha_ejecucion' => 'date:Y-m-d',
            'fecha_ingreso_pdf' => 'date:Y-m-d',
        ];
    }

    public function evaluacionMedica(): BelongsTo
    {
        return $this->belongsTo(EvaluacionMedica::class);
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class);
    }
}
