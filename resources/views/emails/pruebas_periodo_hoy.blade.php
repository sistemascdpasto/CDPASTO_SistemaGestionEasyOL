<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pruebas programadas para hoy</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f8;
            color: #333333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }
        .header {
            background-color: #E3A11E;
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }
        .content {
            padding: 24px;
        }
        p {
            font-size: 15px;
            line-height: 1.5;
            color: #4b5563;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        th {
            background-color: #f8fafc;
            color: #1e293b;
            font-weight: 600;
            text-align: left;
            padding: 10px 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-7 { background-color: #fef3c7; color: #92400e; }
        .badge-30 { background-color: #dbeafe; color: #1e40af; }
        .badge-90 { background-color: #f3e8ff; color: #6b21a8; }
        .total-box {
            background-color: #f1f5f9;
            padding: 12px 16px;
            border-radius: 6px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 16px;
            text-align: right;
        }
        .btn-container {
            text-align: center;
            margin-top: 24px;
            margin-bottom: 16px;
        }
        .btn {
            background-color: #E3A11E;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
        }
        .footer {
            background-color: #f8fafc;
            padding: 16px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sistema de Gestión EASY LOGÍSTICA</h1>
            <div style="font-size: 14px; opacity: 0.9; margin-top: 4px;">Notificación Diaria de Pruebas de Período de Prueba</div>
        </div>
        <div class="content">
            <p>Buenos días,</p>
            <p>Estas son las pruebas que corresponden realizar <strong>hoy, {{ $fechaFormateada }}</strong>:</p>

            <table>
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th>Cargo</th>
                        <th>Prueba</th>
                        <th>Fecha programada</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item['colaborador'] }}</strong><br>
                                <span style="font-size: 12px; color: #64748b;">C.C. {{ $item['cedula'] }}</span>
                            </td>
                            <td>{{ $item['cargo'] }}</td>
                            <td>
                                <span class="badge badge-{{ str_replace('_dias', '', $item['etapa_key']) }}">
                                    {{ $item['etapa_label'] }}
                                </span>
                            </td>
                            <td>{{ $item['fecha_programada'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="total-box">
                Total de pruebas programadas para hoy: {{ count($items) }}
            </div>

            <div class="btn-container">
                <a href="{{ $urlModulo }}" class="btn">Ver Seguimiento de Pruebas</a>
            </div>
        </div>
        <div class="footer">
            Este es un mensaje automático del Sistema Integral de Gestión EASY LOGÍSTICA. Por favor no responder a este correo.
        </div>
    </div>
</body>
</html>
