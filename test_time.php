<?php
require 'vendor/autoload.php';

use Carbon\Carbon;

function parseTimeToMinutes(string $raw): ?int {
    if ($raw === '') return null;
    if (is_numeric($raw)) {
        $val = (float) $raw;
        if ($val == (int)$val && $val >= 0 && $val < 1441) return (int)$val;
        return (int) round($val * 1440);
    }

    $str = trim($raw);
    $isPm = (bool) preg_match('/p\.\s*m\.|pm/i', $str);
    $isAm = (bool) preg_match('/a\.\s*m\.|am/i', $str);

    $strClean = trim(preg_replace('/\s*(a\.\s*m\.|p\.\s*m\.|am|pm)/i', '', $str));

    if (preg_match('/^(\d{1,3}):(\d{2})(?::(\d{2}))?$/', $strClean, $m)) {
        $horas    = (int) $m[1];
        $minutos  = (int) $m[2];
        $segundos = isset($m[3]) ? (int) $m[3] : 0;

        if ($isPm && $horas < 12) {
            $horas += 12;
        } elseif ($isAm && $horas === 12) {
            $horas = 0;
        }

        return $horas * 60 + $minutos + (int) round($segundos / 60);
    }

    return is_numeric($strClean) ? (int)$strClean : null;
}

function parseFechaHora(string $valor): ?string {
    $valor = trim($valor);
    if ($valor === '') return null;

    $valorNorm = preg_replace('/\s*a\.\s*m\.\s*/i', ' AM ', $valor);
    $valorNorm = preg_replace('/\s*p\.\s*m\.\s*/i', ' PM ', $valorNorm);
    $valorNorm = preg_replace('/\s*a\.\s*m\s*/i', ' AM ', $valorNorm);
    $valorNorm = preg_replace('/\s*p\.\s*m\s*/i', ' PM ', $valorNorm);
    $valorNorm = preg_replace('/\s+/', ' ', trim($valorNorm));

    foreach (['Y-m-d H:i:s', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y h:i:s A', 'd/m/Y g:i:s A', 'h:i:s A', 'g:i:s A'] as $formato) {
        try {
            return Carbon::createFromFormat($formato, $valorNorm)->toDateTimeString();
        } catch (Throwable) {
            continue;
        }
    }

    try {
        return Carbon::parse($valorNorm)->toDateTimeString();
    } catch (Throwable) {
        return null;
    }
}

$casos = [
    '08:20:00 a. m.',
    '08:20:00 p. m.',
    '10:00:00 a. m.',
    '1:30:00 p. m.',
    '12:15:00 a. m.',
    '28/08/2026 08:20:00 a. m.',
    '28/08/2026 08:20:00 p. m.',
];

echo "=== TEST TIEMPO EN MINUTOS ===\n";
foreach ($casos as $c) {
    $r = parseTimeToMinutes($c);
    echo str_pad('"' . $c . '"', 30) . ' -> ' . ($r === null ? 'null' : $r . ' min') . "\n";
}

echo "\n=== TEST FECHA/HORA STRING ===\n";
foreach ($casos as $c) {
    $r = parseFechaHora($c);
    echo str_pad('"' . $c . '"', 30) . ' -> ' . ($r === null ? 'null' : $r) . "\n";
}

