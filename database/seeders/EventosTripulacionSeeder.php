<?php

namespace Database\Seeders;

use App\Models\Reparto\EventosTripulacion;
use App\Models\Seguridad\Colaborador;
use Illuminate\Database\Seeder;

class EventosTripulacionSeeder extends Seeder
{
    public function run(): void
    {
        $colaboradores = Colaborador::whereNotNull('cedula')->where('cedula', '!=', '')->get(['cedula', 'nombres', 'apellidos', 'cargo']);

        if ($colaboradores->isEmpty()) {
            $nombresEjemplo = [
                ['cedula' => '1004624253', 'nombres' => 'ÁLVARO ALEXANDER', 'apellidos' => 'CERÓN RAMÍREZ', 'cargo' => 'AUXILIAR DE REPARTO'],
                ['cedula' => '1085291834', 'nombres' => 'CARLOS ANDRÉS',     'apellidos' => 'MENDOZA GÓMEZ', 'cargo' => 'CONDUCTOR DE REPARTO'],
                ['cedula' => '1085304921', 'nombres' => 'JUAN DAVID',        'apellidos' => 'MARTÍNEZ LÓPEZ', 'cargo' => 'CONDUCTOR DE REPARTO'],
                ['cedula' => '1085412890', 'nombres' => 'DIEGO FERNANDO',    'apellidos' => 'RODRÍGUEZ SILVA', 'cargo' => 'AUXILIAR DE REPARTO'],
                ['cedula' => '1085523901', 'nombres' => 'LUIS FELIPE',       'apellidos' => 'RUIZ BENAVIDES', 'cargo' => 'CONDUCTOR DE REPARTO'],
                ['cedula' => '1085634012', 'nombres' => 'EDGAR ALEXANDER',   'apellidos' => 'PATIÑO ERAZO',   'cargo' => 'AUXILIAR DE REPARTO'],
                ['cedula' => '1085745123', 'nombres' => 'OSCAR EDUARDO',     'apellidos' => 'ROSERO SOLARTE', 'cargo' => 'CONDUCTOR DE REPARTO'],
                ['cedula' => '1085856234', 'nombres' => 'HECTOR FABIO',      'apellidos' => 'BURBANO PAREDES', 'cargo' => 'AUXILIAR DE REPARTO'],
                ['cedula' => '1085967345', 'nombres' => 'HAROLD ALBERTO',    'apellidos' => 'CABRERA VALLEJO', 'cargo' => 'CONDUCTOR DE REPARTO'],
                ['cedula' => '1086078456', 'nombres' => 'JHON JAIRO',        'apellidos' => 'DELGADO GUERRERO', 'cargo' => 'AUXILIAR DE REPARTO'],
            ];

            foreach ($nombresEjemplo as $data) {
                Colaborador::firstOrCreate(
                    ['cedula' => $data['cedula']],
                    [
                        'nombres'   => $data['nombres'],
                        'apellidos' => $data['apellidos'],
                        'cargo'     => $data['cargo'],
                        'estado'    => 'ACTIVO',
                    ]
                );
            }
            $colaboradores = Colaborador::whereNotNull('cedula')->where('cedula', '!=', '')->get(['cedula', 'nombres', 'apellidos', 'cargo']);
        }

        $placas = ['COPSX019', 'COUYW793', 'COLJT758', 'COLCN242', 'COPZA812', 'COTRX145', 'COKMY920', 'COWLB531'];
        $batch = [];
        $now = now()->toDateTimeString();
        $fechaFin = now();
        $fechaInicio = now()->subDays(30);

        for ($date = clone $fechaInicio; $date->lte($fechaFin); $date->addDay()) {
            if ($date->isSunday() && rand(0, 1) === 1) continue;
            $fechaStr = $date->format('Y-m-d');
            $anioVal  = (int)$date->format('Y');
            $mesVal   = (int)$date->format('n');

            foreach ($colaboradores as $index => $colab) {
                $placa = $placas[($index + $date->day) % count($placas)];
                $docLimpio = trim((string)$colab->cedula);
                $nombreCompleto = trim("{$colab->nombres} {$colab->apellidos}");
                $exists = EventosTripulacion::where('fecha', $fechaStr)->where('placa', $placa)->where('documento', $docLimpio)->exists();
                if ($exists) continue;

                $batch[] = [
                    'fecha'                      => $fechaStr,
                    'placa'                      => $placa,
                    'doc_transporte'             => 'TRSP-' . rand(1000, 9999),
                    'anio'                       => $anioVal,
                    'mes'                        => $mesVal,
                    'documento'                  => $docLimpio,
                    'nombre'                     => $nombreCompleto,
                    'cargo'                      => $colab->cargo ?? 'AUXILIAR DE REPARTO',
                    'excesos_tiempo_ruta'        => rand(0, 1) === 1 ? rand(0, 180) : 0,
                    'alertas_velocidad_curvas'   => rand(0, 100) > 75 ? rand(1, 4) : 0,
                    'adherencia_checklist_pre'   => round(rand(8500, 10000) / 100, 2),
                    'adherencia_checklist_post'  => round(rand(8500, 10000) / 100, 2),
                    'rendimiento_combustible'    => round(rand(8000, 9800) / 100, 2),
                    'modulacion'                 => round(rand(90, 100) / 100, 2),
                    'adherencia_tiempo'          => round(rand(8500, 10000) / 100, 2),
                    'entrega_en_rango'           => round(rand(8000, 10000) / 100, 2),
                    'rechazos'                   => round(rand(0, 450) / 100, 2),
                    'rmd'                        => round(rand(38, 50) / 10, 1),
                    'created_at'                 => $now,
                    'updated_at'                 => $now,
                ];
                if (count($batch) >= 200) {
                    EventosTripulacion::insert($batch);
                    $batch = [];
                }
            }
        }
        if (!empty($batch)) EventosTripulacion::insert($batch);
        echo "OK: EventosTripulacionSeeder generado e insertado.\n";
    }
}