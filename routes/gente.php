<?php

use App\Http\Controllers\Gente\AusentismoController;
use App\Http\Controllers\Gente\ColaboradorCalificacionController;
use App\Http\Controllers\Gente\ColaboradorController;
use App\Http\Controllers\Gente\ColaboradorEntrenamientoController;
use App\Http\Controllers\Gente\ColaboradorImportController;
use App\Http\Controllers\Gente\CorreccionMarcacionController;
use App\Http\Controllers\Gente\DpoAcademyController;
use App\Http\Controllers\Gente\FestivoCustomController;
use App\Http\Controllers\Gente\GeovictoriaAsistenciaController;
use App\Http\Controllers\Gente\IncentivosController;
use App\Http\Controllers\Gente\LlamadoAtencionController;
use App\Http\Controllers\Gente\PlanPremiacionController;
use App\Http\Controllers\Gente\ReferenciaExternaController;
use App\Http\Controllers\Gente\ResponsableRutaController;
use App\Http\Controllers\Gente\SeguimientoPruebasController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:Administrador|Gente'])
    ->prefix('modules/gente')
    ->name('gente.')
    ->group(function () {
        Route::get('colaboradores/referencias/departamentos', [ReferenciaExternaController::class, 'departamentos'])
            ->name('colaboradores.referencias.departamentos');
        Route::get('colaboradores/referencias/ciudades', [ReferenciaExternaController::class, 'ciudades'])
            ->name('colaboradores.referencias.ciudades');
        Route::get('colaboradores/referencias/instituciones-sena', [ReferenciaExternaController::class, 'institucionesSena'])
            ->name('colaboradores.referencias.instituciones-sena');

        Route::post('colaboradores/importar', [ColaboradorImportController::class, 'store'])
            ->name('colaboradores.importar');

        Route::resource('colaboradores', ColaboradorController::class)
            ->parameters(['colaboradores' => 'colaborador'])
            ->only(['create', 'store', 'edit', 'update', 'destroy']);

        Route::get('colaboradores/{colaborador}/wizard', [ColaboradorController::class, 'wizard'])
            ->name('colaboradores.wizard');
        Route::patch('colaboradores/{colaborador}/paso-1', [ColaboradorController::class, 'updatePaso1'])
            ->name('colaboradores.paso1.update');
        Route::patch('colaboradores/{colaborador}/paso-2', [ColaboradorController::class, 'updatePaso2'])
            ->name('colaboradores.paso2.update');
        Route::patch('colaboradores/{colaborador}/paso-3', [ColaboradorController::class, 'updatePaso3'])
            ->name('colaboradores.paso3.update');
        Route::patch('colaboradores/{colaborador}/paso-4', [ColaboradorController::class, 'updatePaso4'])
            ->name('colaboradores.paso4.update');

        Route::patch('colaboradores/{colaborador}/toggle-activo', [ColaboradorController::class, 'toggleActivo'])
            ->name('colaboradores.toggle-activo');

        Route::post('colaboradores/{colaborador}/llamados-atencion', [LlamadoAtencionController::class, 'store'])
            ->name('colaboradores.llamados-atencion.store');
        Route::post('colaboradores/{colaborador}/entrenamientos', [ColaboradorEntrenamientoController::class, 'store'])
            ->name('colaboradores.entrenamientos.store');

        Route::post('plan-premiacion/toggle-checklist', [PlanPremiacionController::class, 'toggleChecklist'])
            ->name('plan-premiacion.toggle-checklist');

        Route::get('plan-padrinos', [SeguimientoPruebasController::class, 'index'])
            ->name('plan-padrinos.index');
        Route::post('plan-padrinos/toggle', [SeguimientoPruebasController::class, 'toggle'])
            ->name('plan-padrinos.toggle');

        Route::post('calificaciones/importar', [ColaboradorCalificacionController::class, 'importar'])
            ->name('calificaciones.importar');
        Route::post('calificaciones/limpiar', [ColaboradorCalificacionController::class, 'limpiar'])
            ->name('calificaciones.limpiar');

        Route::post('dpo-academy/importar', [DpoAcademyController::class, 'importar'])
            ->name('dpo-academy.importar');
        Route::post('dpo-academy/limpiar', [DpoAcademyController::class, 'limpiar'])
            ->name('dpo-academy.limpiar');

        Route::post('ausentismo/importar', [AusentismoController::class, 'importar'])
            ->name('ausentismo.importar');
        Route::post('ausentismo/limpiar', [AusentismoController::class, 'limpiar'])
            ->name('ausentismo.limpiar');

        Route::post('festivos-custom/toggle', [FestivoCustomController::class, 'toggle'])
            ->name('festivos-custom.toggle');

        Route::post('responsable-ruta/inicio', [ResponsableRutaController::class, 'storeInicio'])
            ->name('responsable-ruta.inicio');
        Route::post('responsable-ruta/finalizacion', [ResponsableRutaController::class, 'storeFin'])
            ->name('responsable-ruta.finalizacion');

        Route::post('incentivos/importar', [IncentivosController::class, 'store'])
            ->name('incentivos.importar');
    });

Route::middleware(['auth', 'active', 'role:Administrador|Seguridad|Flota|Gente'])
    ->prefix('modules/gente')
    ->name('gente.')
    ->group(function () {
        Route::resource('colaboradores', ColaboradorController::class)
            ->parameters(['colaboradores' => 'colaborador'])
            ->only(['index', 'show']);

        Route::get('plan-padrinos/alertas-bell', [SeguimientoPruebasController::class, 'alertasBell'])
            ->name('plan-padrinos.alertas-bell');

        Route::get('plan-premiacion', [PlanPremiacionController::class, 'index'])
            ->name('plan-premiacion.index');
        Route::get('plan-premiacion/exportar', [PlanPremiacionController::class, 'exportar'])
            ->name('plan-premiacion.exportar');
        Route::get('plan-premiacion/{colaborador}', [PlanPremiacionController::class, 'show'])
            ->name('plan-premiacion.show');

        Route::get('responsable-ruta', [ResponsableRutaController::class, 'index'])
            ->name('responsable-ruta.index');

        Route::get('festivos-custom', [FestivoCustomController::class, 'index'])
            ->name('festivos-custom.index');

        Route::get('calificaciones', [ColaboradorCalificacionController::class, 'index'])
            ->name('calificaciones.index');
        Route::get('calificaciones/exportar', [ColaboradorCalificacionController::class, 'exportar'])
            ->name('calificaciones.exportar');

        Route::get('dpo-academy', [DpoAcademyController::class, 'index'])
            ->name('dpo-academy.index');
        Route::get('dpo-academy/exportar', [DpoAcademyController::class, 'exportar'])
            ->name('dpo-academy.exportar');

        Route::get('ausentismo', [AusentismoController::class, 'index'])
            ->name('ausentismo.index');
        Route::get('ausentismo/exportar', [AusentismoController::class, 'exportar'])
            ->name('ausentismo.exportar');

        Route::get('incentivos', [IncentivosController::class, 'index'])
            ->name('incentivos.index');

        Route::get('incentivos/variable', [IncentivosController::class, 'variable'])
            ->name('incentivos.variable');
    });

Route::middleware(['auth', 'active', 'role:Administrador|Gente'])
    ->prefix('modules/gente')
    ->name('gente.')
    ->group(function () {
        Route::get('asistencia-geovictoria', [GeovictoriaAsistenciaController::class, 'index'])
            ->name('asistencia-geovictoria.index');
    });

Route::middleware(['auth', 'active', 'role:Administrador|Gente|Seguridad'])
    ->prefix('modules/gente')
    ->name('gente.')
    ->group(function () {
        Route::post('correccion-marcaciones/preview',  [CorreccionMarcacionController::class, 'preview'])  ->name('correccion-marcaciones.preview');
        Route::post('correccion-marcaciones/importar', [CorreccionMarcacionController::class, 'importar']) ->name('correccion-marcaciones.importar');
        Route::post('correccion-marcaciones/limpiar',  [CorreccionMarcacionController::class, 'limpiar'])  ->name('correccion-marcaciones.limpiar');
        Route::delete('correccion-marcaciones/{id}',   [CorreccionMarcacionController::class, 'destroy'])  ->name('correccion-marcaciones.destroy');
    });

Route::middleware(['auth', 'active', 'role:Administrador|Seguridad|Gente'])
    ->prefix('modules/gente')
    ->name('gente.')
    ->group(function () {
        Route::get('correccion-marcaciones',              [CorreccionMarcacionController::class, 'index'])    ->name('correccion-marcaciones.index');
        Route::get('correccion-marcaciones/exportar.csv', [CorreccionMarcacionController::class, 'exportar']) ->name('correccion-marcaciones.exportar');
        Route::get('correccion-marcaciones/plantilla.csv',[CorreccionMarcacionController::class, 'plantilla'])->name('correccion-marcaciones.plantilla');
    });
