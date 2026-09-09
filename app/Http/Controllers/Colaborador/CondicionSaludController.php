<?php

namespace App\Http\Controllers\Colaborador;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\CondicionSalud;
use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\PruebaAlcoholemia;
use App\Services\Seguridad\EvaluacionCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CondicionSaludController extends Controller
{
    public function create(Request $request, EvaluacionCalculator $evaluacion): Response
    {
        $colaborador = $this->colaboradorDeOFallar($request);

        $firmaPrueba = $this->ultimaPruebaConFirma($colaborador);

        $registrosHoy = CondicionSalud::query()
            ->where('colaborador_id', $colaborador->id)
            ->whereDate('fecha_hora', Carbon::today())
            ->get()
            ->keyBy('momento')
            ->map(fn (CondicionSalud $condicion) => [
                'estado' => $condicion->estado,
                'observacion' => $condicion->observacion,
            ]);

        return Inertia::render('colaborador/condicion-salud', [
            'colaborador' => [
                'nombre_completo' => $colaborador->nombre_completo,
                'cargo' => $colaborador->cargo,
                'area' => $colaborador->area,
            ],
            'puedeFirmar' => $firmaPrueba !== null,
            'firmaPrueba' => $firmaPrueba ? [
                'fecha_hora' => $firmaPrueba->fecha_hora,
                'tipo' => $firmaPrueba->tipo,
                'firma_url' => '/storage/'.$firmaPrueba->firma_path,
            ] : null,
            'registrosHoy' => $registrosHoy,
            'jornadaAbierta' => $evaluacion->jornadaAbierta($colaborador),
            'consentimiento' => config('seguridad.consentimiento_condicion_salud'),
        ]);
    }

    public function store(Request $request, EvaluacionCalculator $evaluacion): RedirectResponse
    {
        $colaborador = $this->colaboradorDeOFallar($request);

        $request->validate([
            'momento' => ['required', Rule::in(['ingreso', 'salida'])],
            'estado' => ['required', Rule::in(['Bueno', 'Regular', 'Malo'])],
            'observacion' => ['required_if:estado,Regular', 'required_if:estado,Malo', 'nullable', 'string', 'max:2000'],
            'consentimiento_aceptado' => ['accepted'],
        ]);

        $firmaPrueba = $this->ultimaPruebaConFirma($colaborador);

        if (! $firmaPrueba) {
            return back()->withErrors([
                'consentimiento_aceptado' => 'Debes tener al menos una prueba de alcoholemia realizada con firma registrada antes de poder autorizar tu firma digital.',
            ]);
        }

        if ($request->input('momento') === 'ingreso' && $evaluacion->jornadaAbierta($colaborador)) {
            return back()->withErrors([
                'momento' => 'Tienes una jornada sin cerrar (tu último ingreso no tiene salida registrada). Registra tu salida antes de un nuevo ingreso.',
            ]);
        }

        $existeHoy = CondicionSalud::query()
            ->where('colaborador_id', $colaborador->id)
            ->where('momento', $request->input('momento'))
            ->whereDate('fecha_hora', Carbon::today())
            ->exists();

        if ($existeHoy) {
            return back()->withErrors([
                'momento' => 'Ya registraste tu '.$request->input('momento').' de hoy. No es posible modificarlo.',
            ]);
        }

        CondicionSalud::create([
            'colaborador_id' => $colaborador->id,
            'momento' => $request->input('momento'),
            'estado' => $request->input('estado'),
            'observacion' => $request->input('observacion'),
            'responsable_id' => $request->user()->id,
            'fecha_hora' => Carbon::now(),
            'prueba_alcoholemia_id' => $firmaPrueba->id,
            'consentimiento_aceptado' => true,
            'consentimiento_en' => Carbon::now(),
            'consentimiento_ip' => $request->ip(),
            'consentimiento_texto_version' => config('seguridad.consentimiento_condicion_salud.version'),
        ]);

        return to_route('portal.condicion-salud.historial')->with('status', 'Condición de salud registrada correctamente.');
    }

    public function historial(Request $request, EvaluacionCalculator $evaluacion): Response
    {
        $colaborador = $this->colaboradorDeOFallar($request);

        $registros = CondicionSalud::query()
            ->where('colaborador_id', $colaborador->id)
            ->latest('fecha_hora')
            ->paginate(15);

        return Inertia::render('colaborador/condicion-salud/historial', [
            'registros' => $registros,
            'jornadaAbierta' => $evaluacion->faltaRegistrarSalida($colaborador),
        ]);
    }

    private function colaboradorDeOFallar(Request $request): Colaborador
    {
        return $request->user()->colaborador ?? abort(
            403,
            'Tu cuenta todavía no está vinculada a un registro de colaborador. Contacta a un administrador.'
        );
    }

    private function ultimaPruebaConFirma(Colaborador $colaborador): ?PruebaAlcoholemia
    {
        return PruebaAlcoholemia::query()
            ->where('colaborador_id', $colaborador->id)
            ->whereNotNull('firma_path')
            ->latest('fecha_hora')
            ->first();
    }
}
