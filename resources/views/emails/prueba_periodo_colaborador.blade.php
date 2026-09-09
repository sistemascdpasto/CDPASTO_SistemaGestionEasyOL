<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoy debes realizar tu prueba de {{ $etapaLabel }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f8;
            color: #333333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
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
            font-size: 22px;
            font-weight: 700;
        }
        .content {
            padding: 28px;
        }
        p {
            font-size: 15px;
            line-height: 1.6;
            color: #374151;
            margin-bottom: 16px;
        }
        .highlight-box {
            background-color: #fef3c7;
            border-left: 4px solid #E3A11E;
            padding: 16px;
            border-radius: 4px;
            margin: 20px 0;
            color: #78350f;
            font-size: 14px;
        }
        .btn-container {
            text-align: center;
            margin-top: 28px;
            margin-bottom: 28px;
        }
        .btn {
            background-color: #E3A11E;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 15px;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(227, 161, 30, 0.3);
        }
        .email-images {
            margin-top: 24px;
            text-align: center;
        }
        .email-images img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            margin-bottom: 12px;
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
            <div style="font-size: 14px; opacity: 0.9; margin-top: 4px;">Seguimiento de Período de Prueba</div>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $nombreCompleto }}</strong>,</p>

            <p>Te informamos que hoy, <strong>{{ $fechaFormateada }}</strong>, debes realizar tu <strong>prueba de {{ $etapaLabel }}</strong> correspondiente al seguimiento de tu proceso de ingreso.</p>

            <div class="highlight-box">
                👤 <strong>Colaborador:</strong> {{ $nombreCompleto }}<br>
                🆔 <strong>Identificación:</strong> C.C. {{ $cedula }}<br>
                💼 <strong>Cargo:</strong> {{ $cargo }}<br>
                📌 <strong>Prueba a realizar:</strong> Prueba de {{ $etapaLabel }}<br>
                📅 <strong>Fecha programada:</strong> {{ $fechaFormateada }}
            </div>

            {{-- Renderizar imágenes adjuntas presentes en public/images/emails/pruebas-periodo si existen --}}
            @if(!empty($imagenes) && count($imagenes) > 0)
                <div class="email-images">
                    @foreach($imagenes as $imgUrl)
                        <img src="{{ $imgUrl }}" alt="Imagen adjunta">
                    @endforeach
                </div>
            @endif
        </div>
        <div class="footer">
            Este es un mensaje automático del Sistema Integral de Gestión EASY LOGÍSTICA. Por favor no responder a este correo.
        </div>
    </div>
</body>
</html>
