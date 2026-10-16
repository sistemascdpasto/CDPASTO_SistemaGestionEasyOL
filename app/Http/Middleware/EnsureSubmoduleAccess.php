<?php

namespace App\Http\Middleware;

use App\Support\ModuleAccessRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Capa adicional de control de acceso, por encima de los `role:` existentes:
 * deriva el (módulo, submódulo) de la URL y verifica contra
 * ModuleAccessRegistry (rol + personalización por usuario, ver esa clase).
 *
 * Se agrega al arreglo de middleware de nivel superior de cada archivo de
 * rutas por módulo (ej. ['auth', 'active', 'submodule.access']) en vez de
 * envolver cada grupo de funcionalidad por separado: como deriva todo de la
 * URL, una sola declaración por archivo cubre todas sus rutas.
 *
 * Si el segundo segmento de la URL no tiene entrada en config/modulos.php
 * (ej. "incentivos/variable"), ModuleAccessRegistry::canAccess() ya resuelve
 * eso devolviendo true (ver comentario en esa clase) — no se necesita lógica
 * especial aquí para submódulos sin catálogo.
 */
class EnsureSubmoduleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $segments = $request->segments();

        if (($segments[0] ?? null) !== 'modules' || ! isset($segments[1], $segments[2])) {
            return $next($request);
        }

        abort_unless(ModuleAccessRegistry::canAccess($user, $segments[1], $segments[2]), 403);

        return $next($request);
    }
}
