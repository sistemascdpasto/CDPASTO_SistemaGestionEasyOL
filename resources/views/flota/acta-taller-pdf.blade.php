<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acta de Taller {{ $acta->numero_acta }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; padding: 18px; }

        /* Encabezado */
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; border-bottom: 2px solid #15803d; padding-bottom: 8px; }
        .header h1 { font-size: 16px; color: #15803d; }
        .header .numero { font-size: 13px; font-weight: bold; color: #374151; }
        .header .fecha { font-size: 8px; color: #6b7280; margin-top: 2px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 8px; font-weight: bold; }
        .badge-cerrada   { background: #15803d; color: #fff; }
        .badge-en_taller { background: #fef3c7; color: #92400e; }
        .badge-cancelada { background: #f3f4f6; color: #6b7280; }

        /* Secciones */
        .seccion { margin-bottom: 12px; }
        .seccion-titulo { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; margin-bottom: 7px; }

        /* Grid de datos */
        .grid { display: flex; flex-wrap: wrap; gap: 6px 16px; }
        .campo { width: 22%; min-width: 110px; }
        .campo-label { font-size: 7.5px; color: #9ca3af; margin-bottom: 1px; }
        .campo-valor { font-size: 9px; color: #111827; font-weight: 500; }

        /* Tabla novedades */
        table { width: 100%; border-collapse: collapse; font-size: 8px; margin-top: 4px; }
        th { background: #f0fdf4; color: #15803d; font-weight: 700; border: 1px solid #d1fae5; padding: 3px 5px; text-align: left; }
        td { border: 1px solid #e5e7eb; padding: 3px 5px; vertical-align: top; color: #374151; }
        tr:nth-child(even) td { background: #f9fafb; }

        /* Badges prioridad */
        .prio-alta   { background:#fee2e2; color:#991b1b; padding:1px 5px; border-radius:9999px; font-size:7px; font-weight:bold; }
        .prio-media  { background:#fef3c7; color:#92400e; padding:1px 5px; border-radius:9999px; font-size:7px; font-weight:bold; }
        .prio-baja   { background:#dbeafe; color:#1e40af; padding:1px 5px; border-radius:9999px; font-size:7px; font-weight:bold; }
        .est-solucionado { background:#dcfce7; color:#166534; padding:1px 5px; border-radius:9999px; font-size:7px; }
        .est-pendiente   { background:#fef9c3; color:#854d0e; padding:1px 5px; border-radius:9999px; font-size:7px; }
        .est-en_revision { background:#dbeafe; color:#1e40af; padding:1px 5px; border-radius:9999px; font-size:7px; }

        /* Firmas */
        .firmas-grid { display: flex; gap: 30px; }
        .firma-bloque { flex: 1; }
        .firma-nombre { font-size: 9px; font-weight: 600; color: #111827; margin-top: 4px; }
        .firma-detalle { font-size: 7.5px; color: #6b7280; }
        .firma-img { max-height: 60px; max-width: 200px; border: 1px solid #e5e7eb; background: #fff; margin-top: 4px; }
        .firma-vacia { width: 200px; height: 60px; border: 1px dashed #d1d5db; margin-top: 4px; }

        /* Footer */
        .footer { margin-top: 16px; border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 7.5px; color: #9ca3af; display: flex; justify-content: space-between; }
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
    $estadoLabel  = $estadoLabels[$acta->estado_acta] ?? $acta->estado_acta;

    $firmaEntregaUri = $imgUri($acta->firma_entrega);
    $firmaRecibeUri  = $imgUri($acta->firma_recibe);
@endphp

<body>

    {{-- ── Encabezado ── --}}
    <div class="header">
        <div>
            <h1>Acta de Entrega a Taller</h1>
            <div class="numero">{{ $acta->numero_acta }}</div>
            <div class="fecha">Generado el {{ now()->format('d/m/Y H:i') }}</div>
        </div>
        <div>
            <span class="badge badge-{{ $acta->estado_acta }}">{{ $estadoLabel }}</span>
            <div style="font-size:9px; color:#374151; margin-top:6px; font-weight:bold;">Vehículo: {{ $acta->placa }}</div>
        </div>
    </div>

    {{-- ── Información del vehículo ── --}}
    <div class="seccion">
        <div class="seccion-titulo">Información del Vehículo</div>
        <div class="grid">
            <div class="campo"><div class="campo-label">Placa</div><div class="campo-valor">{{ $acta->placa }}</div></div>
            <div class="campo"><div class="campo-label">Fecha de entrega</div><div class="campo-valor">{{ $acta->fecha_entrega?->format('d/m/Y H:i') ?? '—' }}</div></div>
            <div class="campo"><div class="campo-label">Kilometraje entrada</div><div class="campo-valor">{{ $acta->kilometraje_entrada ? number_format($acta->kilometraje_entrada).' km' : '—' }}</div></div>
            <div class="campo"><div class="campo-label">Combustible</div><div class="campo-valor">{{ $acta->combustible !== null ? $acta->combustible.'%' : '—' }}</div></div>
            <div class="campo"><div class="campo-label">Taller</div><div class="campo-valor">{{ $acta->taller ?? '—' }}</div></div>
            <div class="campo"><div class="campo-label">Motivo de ingreso</div><div class="campo-valor">{{ $acta->motivo_ingreso ?? '—' }}</div></div>
            <div class="campo"><div class="campo-label">Quien reporta</div><div class="campo-valor">{{ $acta->quien_reporta ?? '—' }}</div></div>
            @if($acta->fecha_estimada_solucion)
            <div class="campo"><div class="campo-label">Fecha est. solución</div><div class="campo-valor">{{ $acta->fecha_estimada_solucion->format('d/m/Y H:i') }}</div></div>
            @endif
            @if($acta->fecha_cierre)
            <div class="campo"><div class="campo-label">Fecha de cierre</div><div class="campo-valor">{{ $acta->fecha_cierre->format('d/m/Y H:i') }}</div></div>
            @endif
        </div>
    </div>

    {{-- ── Novedades ── --}}
    @if($acta->novedades->isNotEmpty())
    <div class="seccion">
        <div class="seccion-titulo">Novedades Reportadas ({{ $acta->novedades->count() }})</div>
        <table>
            <thead>
                <tr>
                    <th style="width:3%">#</th>
                    <th style="width:40%">Descripción</th>
                    <th style="width:10%">Prioridad</th>
                    <th style="width:12%">Estado</th>
                    <th style="width:13%">Reporte</th>
                    <th style="width:22%">Observación solución</th>
                </tr>
            </thead>
            <tbody>
                @foreach($acta->novedades as $i => $nov)
                <tr>
                    <td>{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $nov->titulo }}</td>
                    <td><span class="prio-{{ $nov->prioridad }}">{{ strtoupper($nov->prioridad) }}</span></td>
                    <td>
                        <span class="est-{{ $nov->estado }}">
                            {{ $nov->estado === 'solucionado' ? 'Solucionado' : ($nov->estado === 'en_revision' ? 'En revisión' : 'Pendiente') }}
                        </span>
                    </td>
                    <td>{{ $nov->fecha_reporte?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $nov->observacion_solucion ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── Diagnóstico y solución ── --}}
    @if($acta->diagnostico_taller || $acta->solucion_realizada || $acta->observaciones)
    <div class="seccion">
        <div class="seccion-titulo">Diagnóstico y Solución</div>
        <div class="grid">
            @if($acta->diagnostico_taller)
            <div class="campo" style="width:48%">
                <div class="campo-label">Diagnóstico del taller</div>
                <div class="campo-valor">{{ $acta->diagnostico_taller }}</div>
            </div>
            @endif
            @if($acta->solucion_realizada)
            <div class="campo" style="width:48%">
                <div class="campo-label">Solución realizada</div>
                <div class="campo-valor">{{ $acta->solucion_realizada }}</div>
            </div>
            @endif
            @if($acta->observaciones)
            <div class="campo" style="width:100%; margin-top:4px">
                <div class="campo-label">Observaciones</div>
                <div class="campo-valor">{{ $acta->observaciones }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ── Firmas ── --}}
    <div class="seccion">
        <div class="seccion-titulo">Firmas</div>
        <div class="firmas-grid">

            {{-- Entrega en el taller --}}
            <div class="firma-bloque">
                <div class="seccion-titulo" style="font-size:7px;">ENTREGA EN EL TALLER</div>
                <div class="firma-nombre">{{ $acta->nombre_entrega ?? '—' }}</div>
                @if($acta->identificacion_entrega)
                <div class="firma-detalle">C.C. {{ $acta->identificacion_entrega }}</div>
                @endif
                @if($acta->cargo_entrega)
                <div class="firma-detalle">{{ $acta->cargo_entrega }}</div>
                @endif
                @if($acta->telefono_entrega)
                <div class="firma-detalle">📞 {{ $acta->telefono_entrega }}</div>
                @endif
                @if($firmaEntregaUri)
                    <img class="firma-img" src="{{ $firmaEntregaUri }}" alt="Firma entrega">
                @else
                    <div class="firma-vacia"></div>
                @endif
            </div>

            {{-- Recibe (técnico del taller) --}}
            <div class="firma-bloque">
                <div class="seccion-titulo" style="font-size:7px;">RECIBE (TÉCNICO DEL TALLER)</div>
                <div class="firma-nombre">{{ $acta->nombre_recibe ?? '—' }}</div>
                @if($acta->cargo_recibe)
                <div class="firma-detalle">{{ $acta->cargo_recibe }}</div>
                @endif
                @if($firmaRecibeUri)
                    <img class="firma-img" src="{{ $firmaRecibeUri }}" alt="Firma recibe">
                @else
                    <div class="firma-vacia"></div>
                @endif
            </div>

        </div>
    </div>

    {{-- ── Footer ── --}}
    <div class="footer">
        <span>Acta Nº {{ $acta->numero_acta }} &mdash; {{ $acta->placa }}</span>
        <span>{{ now()->format('d/m/Y H:i') }}</span>
    </div>

</body>
</html>
