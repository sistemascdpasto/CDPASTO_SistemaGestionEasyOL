<?php

namespace App\Exports\Seguridad;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CumplimientoOwdExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, array<string, mixed>>  $filas
     */
    public function __construct(private readonly Collection $filas)
    {
    }

    public function collection(): Collection
    {
        return $this->filas;
    }

    public function headings(): array
    {
        return ['Cédula', 'Colaborador', 'Preguntas evaluadas', 'Preguntas no conformes', 'Porcentaje cumplimiento', 'Resultado'];
    }

    /**
     * @param  array<string, mixed>  $fila
     * @return array<int, mixed>
     */
    public function map($fila): array
    {
        return [
            $fila['cedula'],
            trim("{$fila['nombres']} {$fila['apellidos']}"),
            $fila['total_preguntas'],
            $fila['preguntas_no_conformes'],
            $fila['porcentaje'],
            $fila['cumple'] ? 'CUMPLE' : 'NO CUMPLE',
        ];
    }
}
