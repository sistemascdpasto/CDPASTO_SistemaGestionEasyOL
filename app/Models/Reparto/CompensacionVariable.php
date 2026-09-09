<?php

namespace App\Models\Reparto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompensacionVariable extends Model
{
    use HasFactory;

    protected $table = 'compensaciones_variables';

    protected $fillable = [
        'anio',
        'mes',
        'mes2',
        'regional',
        'cd',
        'codigo_ob',
        'codigo_gp',
        'identificador',
        'nombre',
        'cargo',
        'ausencia_justificada',
        'ausencia_injustificada',
        'tri_fatalidades',
        'adherencia_gp',
        'market_refusals',
        'porcentaje_rechazos',
        'habilitadores',
        'variable',
        'dias_trabajados',
        'salario_variable',
        'pago_variable_dt',
        'total_pago',
    ];

    protected $casts = [
        'anio' => 'integer',
        'ausencia_justificada' => 'float',
        'ausencia_injustificada' => 'float',
        'tri_fatalidades' => 'float',
        'porcentaje_rechazos' => 'float',
        'habilitadores' => 'float',
        'dias_trabajados' => 'float',
        'salario_variable' => 'float',
        'pago_variable_dt' => 'float',
        'total_pago' => 'float',
    ];
}
