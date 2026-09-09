<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureGeovictoriaApiToken;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\EnsureSimitApiToken;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'active' => EnsureAccountIsActive::class,
            'module.access' => EnsureModuleAccess::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'simit.token' => EnsureSimitApiToken::class,
            'geovictoria.token' => EnsureGeovictoriaApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($response->getStatusCode() === 403 && $request->header('X-Inertia')) {
                return Inertia::render('errors/403')->toResponse($request)->setStatusCode(403);
            }

            return $response;
        });
    })->create();
