<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Actas de Taller</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; padding: 14px; }

        .header { border-bottom: 2px solid #15803d; padding-bottom: 7px; margin-bottom: 12px; }
        .header h1 { font-size: 14px; color: #15803d; }
        .header .sub { font-size: 8px; color: #6b7280; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; font-size: 7.5px; }
        th { background: #f0fdf4; color: #15803d; font-weight: 700; border: 1px solid #d1fae5; padding: 3px 4px; text-align: left; white-space: nowrap; }
        td { border: 1px solid #e5e7eb; padding: 3px 4px; vertical-align: middle; color: #374151; }
        tr:nth-child(even) td { background: #f9fafb; }

        .badge { display: inline-block; padding: 1px 5px; border-radius: 9999px; font-size: 6.5px; font-weight: bold; }
        .badge-cerrada   { background: #15803d; color: #fff; }
        .badge-en_taller { background: #fef3c7; color: #92400e; }
        .badge-cancelada { background: #f3f4f6; color: #6b7280; }
        .pend { color: #b45309; font-weight: bold; }
        .sol  { color: #15803d; font-weight: bold; }

        .firma-img { max-height: 28px; max-width: 70px; }

        .footer { margin-top: 10px; border-top: 1px solid #e5e7eb; padding-top: 5px; font-size: 7px; color: #9ca3af; display: flex; justify-content: space-between; }
    </style>
</head>

@php
    $imgUri = function (?string $path): ?string {
        if (! $path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return null;
        }
        $mime     = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($path);
        $contents = \Illuminate\Support\Facades\Storage::disk('public')->get($path);
        return "data:{$mime};base64," . base64_encode($contents);
    };

    $estadoLabels = ['cerrada' => 'Cerrada', 'en_taller' => 'En taller', 'cancelada' => 'Cancelada'];
@endphp

<body>

    <div class="header">
        <h1>Actas de Entrega a Taller</h1>
        <div class="sub">Generado el {{ now()->format('d/m/Y H:i') }} &mdash; {{ $actas->count() }} acta(s)</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nº Acta</th>
                <th>Placa</th>
                <th>Taller</th>
                <th>Fecha Entrega</th>
                <th>Km</th>
                <th>Comb.</th>
                <th>Motivo</th>
                <th>Quien Reporta</th>
                <th>Estado</th>
                <th>Nov.</th>
                <th>Sol.</th>
                <th>Pend.</th>
                <th>Entrega — Nombre</th>
                <th>C.C.</th>
                <th>Cargo</th>
                <th>Teléfono</th>
                <th>Firma Entrega</th>
                <th>Recibe — Nombre</th>
                <th>Firma Recibe</th>
            </tr>
        </thead>
        <tbody>
            @forelse($actas as $acta)
            @php
                $novedades    = $acta->novedades;
                $solucionadas = $novedades->where('estado', 'solucionado')->count();
                $pendientes   = $novedades->where('estado', 'pendiente')->count();
                $firmaE = $imgUri($acta->firma_entrega);
                $firmaR = $imgUri($acta->firma_recibe);
            @endphp
            <tr>
                <td><b>{{ $acta->numero_acta }}</b></td>
                <td>{{ $acta->placa }}</td>
                <td>{{ $acta->taller ?? '—' }}</td>
                <td>{{ $acta->fecha_entrega?->format('d/m/Y H:i') ?? '—' }}</td>
                <td>{{ $acta->kilometraje_entrada ? number_format($acta->kilometraje_entrada) : '—' }}</td>
                <td>{{ $acta->combustible !== null ? $acta->combustible.'%' : '—' }}</td>
                <td>{{ Str::limit($acta->motivo_ingreso ?? '—', 30) }}</td>
                <td>{{ $acta->quien_reporta ?? '—' }}</td>
                <td><span class="badge badge-{{ $acta->estado_acta }}">{{ $estadoLabels[$acta->estado_acta] ?? $acta->estado_acta }}</span></td>
                <td style="text-align:center">{{ $novedades->count() }}</td>
                <td style="text-align:center" class="sol">{{ $solucionadas ?: '—' }}</td>
                <td style="text-align:center" class="{{ $pendientes ? 'pend' : '' }}">{{ $pendientes ?: '—' }}</td>
                <td>{{ $acta->nombre_entrega ?? '—' }}</td>
                <td>{{ $acta->identificacion_entrega ?? '—' }}</td>
                <td>{{ $acta->cargo_entrega ?? '—' }}</td>
                <td>{{ $acta->telefono_entrega ?? '—' }}</td>
                <td>
                    @if($firmaE)
                        <img class="firma-img" src="{{ $firmaE }}" alt="Firma E">
                    @else
                        —
                    @endif
                </td>
                <td>{{ $acta->nombre_recibe ?? '—' }}</td>
                <td>
                    @if($firmaR)
                        <img class="firma-img" src="{{ $firmaR }}" alt="Firma R">
                    @else
                        —
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="19" style="text-align:center; padding:10px; color:#9ca3af;">
                    No hay actas registradas con los filtros seleccionados.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Reporte de Actas de Taller — EASY LOGÍSTICA</span>
        <span>{{ now()->format('d/m/Y H:i') }}</span>
    </div>

</body>
</html>
