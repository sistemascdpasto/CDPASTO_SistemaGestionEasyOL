<?php

namespace App\Http\Controllers\Capacitaciones;

use App\Http\Controllers\Controller;
use App\Models\Capacitaciones\CapacitacionCarpeta;
use App\Models\Capacitaciones\CapacitacionMaterial;
use App\Models\Seguridad\Alerta;
use App\Models\User;
use App\Notifications\Capacitaciones\NotificacionCapacitacionProgramada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MaterialController extends Controller
{
    public function store(Request $request, CapacitacionCarpeta $carpeta): RedirectResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'tipo' => ['nullable', 'string', 'max:50'],
            'archivo' => ['nullable', 'file', 'max:102400'], // hasta 100MB
            'enlace_externo' => ['nullable', 'url', 'max:2048'],
            'destacada' => ['nullable', 'boolean'],
            'fecha_programada' => ['nullable', 'date'],
        ]);

        $archivoPath = null;
        $archivoNombreOriginal = null;
        $tamanoBytes = null;
        $mimeType = null;
        $tipo = $data['tipo'] ?? null;

        if ($request->hasFile('archivo') && $request->file('archivo')->isValid()) {
            $file = $request->file('archivo');
            $archivoNombreOriginal = $file->getClientOriginalName();
            $tamanoBytes = $file->getSize();
            $mimeType = $file->getMimeType();
            $ext = strtolower($file->getClientOriginalExtension());

            if (! $tipo || $tipo === 'auto') {
                $tipo = match ($ext) {
                    'mp4', 'mov', 'avi', 'mkv', 'webm' => 'video',
                    'ppt', 'pptx' => 'presentacion',
                    'xls', 'xlsx', 'csv' => 'hoja_calculo',
                    'pdf' => 'pdf',
                    'doc', 'docx', 'txt' => 'documento',
                    default => 'otro',
                };
            }

            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $archivoNombreOriginal);
            $archivoPath = $file->storeAs('capacitaciones', $safeName, 'public');
        } elseif (! empty($data['enlace_externo'])) {
            if (! $tipo || $tipo === 'auto') {
                $tipo = 'enlace';
            }
        }

        $material = $carpeta->materiales()->create([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'tipo' => $tipo ?? 'documento',
            'archivo_path' => $archivoPath,
            'archivo_nombre_original' => $archivoNombreOriginal,
            'tamano_bytes' => $tamanoBytes,
            'mime_type' => $mimeType,
            'enlace_externo' => $data['enlace_externo'] ?? null,
            'estado' => 'publicado',
            'destacada' => (bool) ($data['destacada'] ?? false),
            'fecha_programada' => $data['fecha_programada'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        // Si se asignó una fecha del calendario, enviar notificaciones por correo y generar alertas
        if (! empty($data['fecha_programada'])) {
            $this->notificarCapacitacionProgramada($material);
        }

        return back()->with('status', 'Material subido exitosamente.');
    }

    public function update(Request $request, CapacitacionMaterial $material): RedirectResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'tipo' => ['nullable', 'string', 'max:50'],
            'enlace_externo' => ['nullable', 'url', 'max:2048'],
            'destacada' => ['nullable', 'boolean'],
            'fecha_programada' => ['nullable', 'date'],
        ]);

        $huboCambioFecha = $data['fecha_programada'] && $data['fecha_programada'] !== $material->fecha_programada?->format('Y-m-d');

        $material->update($data);

        if ($huboCambioFecha) {
            $this->notificarCapacitacionProgramada($material);
        }

        return back()->with('status', 'Material actualizado exitosamente.');
    }

    public function destroy(CapacitacionMaterial $material): RedirectResponse
    {
        if ($material->archivo_path && Storage::disk('public')->exists($material->archivo_path)) {
            Storage::disk('public')->delete($material->archivo_path);
        }

        $material->delete();

        return back()->with('status', 'Material eliminado correctamente.');
    }

    public function descargar(CapacitacionMaterial $material): StreamedResponse|RedirectResponse
    {
        if (! $material->archivo_path || ! Storage::disk('public')->exists($material->archivo_path)) {
            return back()->with('status', [
                'type' => 'error',
                'message' => 'El archivo no se encuentra disponible.',
            ]);
        }

        return Storage::disk('public')->download($material->archivo_path, $material->archivo_nombre_original ?? 'archivo');
    }

    private function notificarCapacitacionProgramada(CapacitacionMaterial $material): void
    {
        $fecha = Carbon::parse($material->fecha_programada);
        $mesNombre = mb_strtoupper($fecha->locale('es')->monthName) . ' DE ' . $fecha->year;

        // Enviar notificaciones a los colaboradores con correo
        $colaboradores = User::role('Colaborador')->whereNotNull('email')->get();

        foreach ($colaboradores as $user) {
            try {
                Notification::send($user, new NotificacionCapacitacionProgramada($material));
            } catch (Throwable $e) {
                Log::warning('No se pudo enviar el correo de la capacitación programada.', [
                    'material_id' => $material->id,
                    'user_email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }

            // Crear alerta interna si la tabla de alertas existe
            try {
                if ($user->colaborador_id || $user->colaborador) {
                    Alerta::create([
                        'colaborador_id' => $user->colaborador?->id,
                        'tipo' => 'Capacitación Programada',
                        'mensaje' => "La capacitación \"{$material->titulo}\" está programada para {$mesNombre}.",
                        'fecha_alerta' => now(),
                    ]);
                }
            } catch (Throwable $e) {
                // Silenciosamente ignorar si difiere el esquema de alerta
            }
        }
    }
}
