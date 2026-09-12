<?php

namespace App\Models\Flota;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Varada extends Model
{
    protected $table = 'flota_varadas';

    protected $fillable = [
        'placa',
        'fecha_reportada',
        'fecha_asistencia',
        'fecha_solucion',
        'sistema',
        'tipo_falla',
        'descripcion',
        'causa_probable',
        'repetitiva',
        'ruta',
        'lugar',
        'proveedor',
        'tipo_solucion',
        'impacto',
        'gravedad',
        'observaciones',
        'latitud',
        'longitud',
        'origen',
        'created_by',
    ];

    protected $appends = [
        'tfs_minutos',
        'tfs_horas',
        'dias_fs',
        'gt_horas',
        'esta_abierta',
    ];

    protected function casts(): array
    {
        return [
            'fecha_reportada' => 'datetime',
            'fecha_asistencia' => 'datetime',
            'fecha_solucion' => 'datetime',
            'repetitiva' => 'boolean',
            'gravedad' => 'integer',
            'latitud' => 'decimal:6',
            'longitud' => 'decimal:6',
        ];
    }

    /**
     * TFS = tiempo fuera de servicio, desde que se reporta la varada hasta
     * que queda solucionada. Se calcula acá en vez de confiar en las
     * columnas del Excel original: para varadas sin resolver (fecha_solucion
     * nula) esas columnas traen valores corruptos porque Excel resta contra
     * una celda vacía.
     */
    protected function tfsMinutos(): Attribute
    {
        return Attribute::get(fn () => $this->fecha_solucion && $this->fecha_reportada
            ? $this->fecha_reportada->diffInMinutes($this->fecha_solucion)
            : null);
    }

    protected function tfsHoras(): Attribute
    {
        return Attribute::get(fn () => $this->tfs_minutos !== null ? round($this->tfs_minutos / 60, 2) : null);
    }

    protected function diasFs(): Attribute
    {
        return Attribute::get(fn () => $this->tfs_minutos !== null ? round($this->tfs_minutos / 1440, 2) : null);
    }

    /**
     * GT = tiempo de gestión, desde que llega la asistencia hasta que queda
     * solucionada (distinto del TFS, que cuenta desde el reporte).
     */
    protected function gtHoras(): Attribute
    {
        return Attribute::get(fn () => $this->fecha_solucion && $this->fecha_asistencia
            ? round($this->fecha_asistencia->diffInMinutes($this->fecha_solucion) / 60, 2)
            : null);
    }

    protected function estaAbierta(): Attribute
    {
        return Attribute::get(fn () => $this->fecha_solucion === null);
    }
}
