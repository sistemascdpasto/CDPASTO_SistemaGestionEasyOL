<?php

namespace Database\Seeders;

use App\Models\Reparto\CompensacionVariable;
use Illuminate\Database\Seeder;

class CompensacionVariableSeeder extends Seeder
{
    public function run(): void
    {
        $regionales = ['Centro', 'Norte', 'Occidente', 'Oriente', 'Sur'];
        $cds = ['CD-101', 'CD-102', 'CD-103', 'CD-104'];
        $cargos = ['Conductor Reparto', 'Auxiliar de Entrega', 'Supervisor de Operaciones', 'Líder de Logística'];
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto'];

        $nombres = [
            'Carlos Alberto Mendoza',
            'María Fernanda Gómez',
            'Juan Esteban Rodríguez',
            'Andrés Felipe Torres',
            'Diana Marcela Morales',
            'Javier Enrique Vargas',
            'Paula Andrea Ríos',
            'Diego Alexander Silva',
            'Laura Catalina Castro',
            'Oscar Eduardo Martínez'
        ];

        foreach ($nombres as $index => $nombre) {
            $salarioVar = rand(800000, 2500000);
            $diasTrabajados = rand(20, 26);
            $varPct = rand(70, 100);
            $variableDecimal = $varPct / 100.0;
            $habilitador = [1.0, 0.8, 1.0, 0.0, 1.0][$index % 5];

            // Formula: =(SalarioVariable / 30) * DiasTrabajados * VariableEnDecimal (* Habilitador if penalty)
            $pagoVariableDt = round(($salarioVar / 30.0) * $diasTrabajados * $variableDecimal * ($habilitador < 1.0 ? $habilitador : 1.0), 2);

            CompensacionVariable::create([
                'anio' => 2026,
                'mes' => $meses[$index % count($meses)],
                'mes2' => '2026-' . str_pad(($index % 12) + 1, 2, '0', STR_PAD_LEFT),
                'regional' => $regionales[$index % count($regionales)],
                'cd' => $cds[$index % count($cds)],
                'codigo_ob' => 'OB-' . (5000 + $index),
                'codigo_gp' => 'GP-' . (8000 + $index),
                'identificador' => (string) (1018200000 + $index),
                'nombre' => $nombre,
                'cargo' => $cargos[$index % count($cargos)],
                'ausencia_justificada' => rand(0, 2),
                'ausencia_injustificada' => $index % 4 === 0 ? 1 : 0,
                'tri_fatalidades' => $index % 7 === 0 ? 1 : 0,
                'adherencia_gp' => rand(85, 100) . '%',
                'market_refusals' => rand(0, 5) . ' POCs',
                'porcentaje_rechazos' => rand(1, 8),
                'habilitadores' => $habilitador,
                'variable' => $varPct . '%',
                'dias_trabajados' => $diasTrabajados,
                'salario_variable' => $salarioVar,
                'pago_variable_dt' => $pagoVariableDt,
                'total_pago' => $pagoVariableDt,
            ]);
        }
    }
}
