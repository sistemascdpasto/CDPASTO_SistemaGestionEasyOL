<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aci extends Model
{
    protected $table = 'acis';

    protected $fillable = [
        'folio',
        'fecha_alzado',
        'hora_alzado',
        'fecha_incidente',
        'hora_incidente',
        'ubicacion',
        'dentro_fuera_ubicacion',
        'zona',
        'subzona',
        'reporte_de',
        'persona_cometio',
        'persona_cometio_pin',
        'persona_cometio_numero_empleado',
        'reportado_por',
        'qr_reportante',
        'colaborador_id',
        'numero_empleado_reporto',
        'pin_reporto',
        'descripcion',
        'posible_solucion',
        'area',
        'subarea',
        'descripcion_ubicacion',
        'clasificacion',
        'tipo_riesgo',
        'descripcion_tipo_riesgo',
        'ciudad_elegida',
        'estado_elegido',
        'nombre_punto_venta',
        'descripcion_punto_venta',
        'tipo_acto_ruta',
        'fue_por_externo',
        'sif',
        'estatus_asignacion',
        'fecha_hora_asignacion',
        'asignado_por',
        'qr_asignador',
        'asignador_colaborador_id',
        'asignado_a',
        'qr_asignado',
        'asignado_colaborador_id',
        'cerrado_por',
        'fecha_hora_cierre',
        'bu',
        'compania',
        'fecha_pospuesto',
        'datos_adicionales',
    ];

    protected function casts(): array
    {
        return [
            'fecha_alzado' => 'date:Y-m-d',
            'fecha_incidente' => 'date:Y-m-d',
            'fecha_hora_asignacion' => 'datetime',
            'fecha_hora_cierre' => 'datetime',
            'fecha_pospuesto' => 'date:Y-m-d',
            'fue_por_externo' => 'boolean',
            'sif' => 'boolean',
            'datos_adicionales' => 'array',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function asignador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'asignador_colaborador_id');
    }

    public function asignado(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'asignado_colaborador_id');
    }
}
