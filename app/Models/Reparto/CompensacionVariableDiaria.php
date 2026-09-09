<?php

namespace App\Models\Reparto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompensacionVariableDiaria extends Model
{
    use HasFactory;

    protected $table = 'compensaciones_variables_diarias';

    protected $fillable = [
        'fecha',
        'anio',
        'mes',
        'placa',
        'transporte',
        'rr',
        'cedula',
        'nombre_completo',
        'cargo',
        'rechazos',
        'cal_rechazos',
        'cal_rechazos_2',
        'valor_x_dia',
        'valor_var',
        'valor_perdido',
        'porcentaje_variable',
        'porcentaje_variable_no_cum',
        'meta_1',
        'meta_2',
    ];

    protected $casts = [
        'fecha' => 'date',
        'anio' => 'integer',
        'rechazos' => 'float',
        'cal_rechazos' => 'float',
        'cal_rechazos_2' => 'float',
        'valor_x_dia' => 'float',
        'valor_var' => 'float',
        'valor_perdido' => 'float',
        'meta_1' => 'float',
        'meta_2' => 'float',
    ];
}
