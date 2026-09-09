<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanAccionOwd extends Model
{
    protected $table = 'plan_accion_owd';

    public const ESTADO_PENDIENTE = 'Pendiente';

    public const ESTADO_EN_PROGRESO = 'En progreso';

    public const ESTADO_COMPLETADO = 'Completado';

    protected $fillable = [
        'evaluacion_owd_pregunta_id',
        'estado',
        'fecha_vencimiento',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_vencimiento' => 'date',
        ];
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(EvaluacionOwdPregunta::class, 'evaluacion_owd_pregunta_id');
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(PlanAccionOwdSeguimiento::class)->latest('fecha');
    }

    public function getVencidoAttribute(): bool
    {
        return $this->estado !== self::ESTADO_COMPLETADO
            && $this->fecha_vencimiento !== null
            && $this->fecha_vencimiento->isPast();
    }

    public function getProximoAVencerAttribute(): bool
    {
        return $this->estado !== self::ESTADO_COMPLETADO
            && $this->fecha_vencimiento !== null
            && ! $this->vencido
            && $this->fecha_vencimiento->diffInDays(now()) <= 7;
    }
}
