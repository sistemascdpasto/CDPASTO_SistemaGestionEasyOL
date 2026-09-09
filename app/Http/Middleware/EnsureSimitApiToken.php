<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege las rutas de ingesta del monitor SIMIT (llamadas por el script
 * Python que corre en la PC local, no por un usuario autenticado en la
 * app). Compara el Bearer token contra SIMIT_API_TOKEN en vez de usar el
 * sistema de sesiones/roles: es una sola maquina de confianza, no un
 * usuario con permisos variables.
 */
class EnsureSimitApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('services.simit.token');
        $provisto = $request->bearerToken();

        if (! $token || ! $provisto || ! hash_equals($token, $provisto)) {
            abort(401, 'Token invalido o ausente.');
        }

        return $next($request);
    }
}
