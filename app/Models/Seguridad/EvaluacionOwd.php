<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluacionOwd extends Model
{
    protected $table = 'evaluaciones_owd';

    protected $fillable = [
        'bu',
        'pais',
        'region',
        'uen',
        'id_agencia',
        'agencia',
        'evaluador',
        'posicion_evaluador',
        'qr_safety_evaluador',
        'sharp_evaluador',
        'evaluador_colaborador_id',
        'evaluado',
        'posicion',
        'qr_safety',
        'sharp',
        'colaborador_id',
        'fecha_evaluacion',
        'type',
        'pillar',
        'total_preguntas',
        'preguntas_ok',
        'preguntas_no_ok',
        'preguntas_na',
    ];

    protected function casts(): array
    {
        return [
            'fecha_evaluacion' => 'datetime',
            'total_preguntas' => 'integer',
            'preguntas_ok' => 'integer',
            'preguntas_no_ok' => 'integer',
            'preguntas_na' => 'integer',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function evaluadorColaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'evaluador_colaborador_id');
    }

    public function preguntas(): HasMany
    {
        return $this->hasMany(EvaluacionOwdPregunta::class);
    }

    /**
     * Recalcula los contadores cacheados a partir de `preguntas()`. Se
     * llama al final de cada importación para los encabezados tocados —
     * evitar que indicadores/listados tengan que agregar la tabla de
     * preguntas en cada consulta.
     */
    public function recalcularContadores(): void
    {
        $conteos = $this->preguntas()
            ->selectRaw("count(*) as total, sum(case when puntuacion = 'OK' then 1 else 0 end) as ok, "
                ."sum(case when puntuacion = 'No OK' then 1 else 0 end) as no_ok, "
                ."sum(case when puntuacion = 'Not Applicable' then 1 else 0 end) as na")
            ->first();

        $this->forceFill([
            'total_preguntas' => (int) ($conteos->total ?? 0),
            'preguntas_ok' => (int) ($conteos->ok ?? 0),
            'preguntas_no_ok' => (int) ($conteos->no_ok ?? 0),
            'preguntas_na' => (int) ($conteos->na ?? 0),
        ])->save();
    }
}
