<?php

namespace App\Services;

use App\Models\Gente\FestivoCustom;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Festivos de Colombia con variantes regionales de Nariño / Pasto.
 * También incorpora los festivos personalizados guardados en la tabla festivos_custom.
 *
 * Fuentes:
 *  - Ley 51 de 1983 (Ley Emiliani): algunos festivos se trasladan al lunes siguiente.
 *  - Decreto 2663 de 1950 y Concordato (festivos religiosos fijos).
 *  - Festivos propios del departamento de Nariño y municipio de Pasto
 *    (Carnaval de Negros y Blancos: 2, 3, 4, 5, 6 de enero).
 *  - Festivos personalizados en tabla festivos_custom.
 */
class FestivosColombiaService
{
    /**
     * Festivos de año fijo que NO se trasladan (siempre caen el mismo día).
     * Formato: ['MM-DD' => 'Nombre']
     */
    private const FIJOS = [
        '01-01' => 'Año Nuevo',
        '05-01' => 'Día del Trabajo',
        '07-20' => 'Independencia de Colombia',
        '08-07' => 'Batalla de Boyacá',
        '12-08' => 'Inmaculada Concepción',
        '12-25' => 'Navidad',

        // Festivos propios de Nariño / Pasto — Carnaval de Negros y Blancos
        '01-02' => 'Carnaval Nariño - Llegada de Año Viejo',
        '01-03' => 'Carnaval Nariño - Día del Agua',
        '01-04' => 'Carnaval Nariño - Día de Familia',
        '01-05' => 'Carnaval de Negros',
        '01-06' => 'Carnaval de Blancos (Reyes Magos)',
    ];

    /**
     * Festivos sujetos a la Ley Emiliani: si no caen en lunes,
     * se trasladan al LUNES siguiente.
     * Formato: ['MM-DD' => 'Nombre']
     */
    private const EMILIANI = [
        '01-06' => 'Reyes Magos',          // 6 enero  (en Nariño es festivo fijo, ver arriba)
        '03-19' => 'San José',             // 19 marzo
        '06-29' => 'San Pedro y San Pablo',// 29 junio
        '08-15' => 'Asunción de la Virgen',// 15 agosto
        '10-12' => 'Día de la Raza',       // 12 octubre
        '11-01' => 'Todos los Santos',     // 1 noviembre
        '11-11' => 'Independencia de Cartagena', // 11 noviembre
    ];

    /**
     * Cache en memoria por año para no recalcular en cada fila.
     * @var array<int, array<string, string>>
     */
    private array $cache = [];

    /**
     * Retorna true si la fecha dada es domingo O festivo en Colombia (Nariño/Pasto).
     */
    public function esDomingoOFestivo(Carbon $fecha): bool
    {
        if ($fecha->dayOfWeek === Carbon::SUNDAY) {
            return true;
        }
        return $this->esFestivo($fecha);
    }

    /**
     * Retorna true si la fecha es festivo nacional o regional (Nariño/Pasto).
     */
    public function esFestivo(Carbon $fecha): bool
    {
        $festivos = $this->festivosDelAnio($fecha->year);
        return isset($festivos[$fecha->format('Y-m-d')]);
    }

    /**
     * Devuelve todos los festivos del año dado (automáticos + personalizados en BD).
     * @return array<string, string>  ['YYYY-MM-DD' => 'Nombre del festivo']
     */
    public function festivosDelAnio(int $anio): array
    {
        if (isset($this->cache[$anio])) {
            return $this->cache[$anio];
        }

        $festivos = [];

        // 1. Festivos fijos
        foreach (self::FIJOS as $mmdd => $nombre) {
            $festivos["{$anio}-{$mmdd}"] = $nombre;
        }

        // 2. Festivos Emiliani (trasladar al lunes si no es lunes)
        foreach (self::EMILIANI as $mmdd => $nombre) {
            $base = Carbon::createFromFormat('Y-m-d', "{$anio}-{$mmdd}");
            if ($base->dayOfWeek !== Carbon::MONDAY) {
                $lunes = $base->copy()->next(Carbon::MONDAY);
            } else {
                $lunes = $base;
            }
            $festivos[$lunes->format('Y-m-d')] = $nombre . ' (trasladado)';
        }

        // 3. Festivos móviles basados en Pascua
        $pascua = $this->calcularPascua($anio);
        $festivos[$pascua->copy()->subDays(3)->format('Y-m-d')] = 'Jueves Santo';
        $festivos[$pascua->copy()->subDays(2)->format('Y-m-d')] = 'Viernes Santo';
        $festivos[$this->siguienteLunes($pascua->copy()->addDays(39))->format('Y-m-d')] = 'Ascensión del Señor';
        $festivos[$this->siguienteLunes($pascua->copy()->addDays(60))->format('Y-m-d')] = 'Corpus Christi';
        $festivos[$this->siguienteLunes($pascua->copy()->addDays(68))->format('Y-m-d')] = 'Sagrado Corazón de Jesús';

        // 4. Festivos personalizados guardados en la BD (tabla festivos_custom)
        try {
            $custom = FestivoCustom::whereYear('fecha', $anio)->get();
            foreach ($custom as $f) {
                $key = $f->fecha instanceof Carbon
                    ? $f->fecha->format('Y-m-d')
                    : (string) $f->fecha;
                $festivos[$key] = $f->nombre . ' (personalizado)';
            }
        } catch (\Throwable) {
            // Si la tabla no existe todavía (entorno de testing / migraciones pendientes),
            // continuamos sin los festivos custom.
        }

        $this->cache[$anio] = $festivos;

        return $festivos;
    }

    /**
     * Retorna el nombre del festivo o null si no es festivo.
     */
    public function nombreFestivo(Carbon $fecha): ?string
    {
        $festivos = $this->festivosDelAnio($fecha->year);
        return $festivos[$fecha->format('Y-m-d')] ?? null;
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    /**
     * Algoritmo de Meeus/Jones/Butcher para calcular la fecha de Pascua.
     */
    private function calcularPascua(int $anio): Carbon
    {
        $a = $anio % 19;
        $b = intdiv($anio, 100);
        $c = $anio % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::createFromDate($anio, $mes, $dia);
    }

    /**
     * Si la fecha ya es lunes la retorna tal cual; si no, retorna el lunes siguiente.
     */
    private function siguienteLunes(Carbon $fecha): Carbon
    {
        if ($fecha->dayOfWeek === Carbon::MONDAY) {
            return $fecha;
        }
        return $fecha->next(Carbon::MONDAY);
    }
}
