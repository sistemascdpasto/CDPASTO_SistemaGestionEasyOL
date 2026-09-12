<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EncuestaMorbilidad extends Model
{
    protected $table = 'encuestas_morbilidad';

    public const ESTADO_BORRADOR = 'borrador';

    public const ESTADO_COMPLETADA = 'completada';

    protected $fillable = [
        'colaborador_id',
        'estado',
        'fecha_hora',
        'enviado_en',

        // Paso 1 — identificación
        'empresa',
        'correo_electronico',
        'edad',
        'estado_civil',

        // Paso 1 — familia
        'tiene_hijos',
        'hijos',
        'personas_a_cargo',
        'personas_cargo_detalle',

        // Paso 1 — educación y vivienda
        'nivel_escolaridad',
        'estrato_socioeconomico',
        'tenencia_vivienda',

        // Paso 1 — residencia
        'ciudad_residencia',
        'direccion_residencia',

        // Paso 1 — información laboral
        'tipo_contratacion',
        'cargo_paso1',
        'area_paso1',
        'antiguedad_empresa',
        'antiguedad_cargo',
        'duracion_contrato',
        'turno',
        'promedio_ingresos',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora'             => 'datetime',
            'enviado_en'             => 'datetime',
            'hijos'                  => 'array',
            'personas_cargo_detalle' => 'array',
            'edad'                   => 'integer',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(EncuestaMorbilidadRespuesta::class);
    }
}
