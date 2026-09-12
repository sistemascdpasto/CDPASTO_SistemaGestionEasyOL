<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\EncuestaMorbilidadSeccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EncuestaMorbilidadSeccionController extends Controller
{
    /** Lista todas las secciones con su portada actual */
    public function index(): Response
    {
        $secciones = EncuestaMorbilidadSeccion::orderBy('numero')->get()
            ->map(fn ($s) => [
                'id'                 => $s->id,
                'numero'             => $s->numero,
                'titulo'             => $s->titulo,
                'descripcion'        => $s->descripcion,
                'imagen_portada'     => $s->imagen_portada,
                'imagen_portada_url' => $s->imagen_portada_url,
                'activo'             => $s->activo,
            ]);

        return Inertia::render('seguridad/encuestas-morbilidad/secciones', [
            'secciones' => $secciones,
        ]);
    }

    /** Sube o reemplaza la imagen de portada de una sección */
    public function subirPortada(Request $request, EncuestaMorbilidadSeccion $seccion): RedirectResponse
    {
        $request->validate([
            'imagen' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'imagen.required' => 'Debes seleccionar una imagen.',
            'imagen.image'    => 'El archivo debe ser una imagen.',
            'imagen.mimes'    => 'Solo se aceptan imágenes JPG, PNG o WebP.',
            'imagen.max'      => 'La imagen no debe superar 4 MB.',
        ]);

        // Eliminar portada anterior si existe
        if ($seccion->imagen_portada && Storage::disk('public')->exists($seccion->imagen_portada)) {
            Storage::disk('public')->delete($seccion->imagen_portada);
        }

        $path = $request->file('imagen')->store(
            'encuestas-morbilidad/secciones',
            'public'
        );

        $seccion->update(['imagen_portada' => $path]);

        return back()->with('status', "Portada de la Sección {$seccion->numero} actualizada correctamente.");
    }

    /** Elimina la imagen de portada de una sección */
    public function eliminarPortada(EncuestaMorbilidadSeccion $seccion): RedirectResponse
    {
        if ($seccion->imagen_portada && Storage::disk('public')->exists($seccion->imagen_portada)) {
            Storage::disk('public')->delete($seccion->imagen_portada);
        }

        $seccion->update(['imagen_portada' => null]);

        return back()->with('status', "Portada de la Sección {$seccion->numero} eliminada.");
    }
}
