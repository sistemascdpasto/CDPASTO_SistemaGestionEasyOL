<?php

use App\Http\Controllers\Flota\ActaTallerController;
use App\Http\Controllers\Flota\SimitConsultaController;
use App\Http\Controllers\Flota\VaradaController;
use App\Http\Controllers\Flota\VehiculoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:Administrador|Flota'])
    ->prefix('modules/flota')
    ->name('flota.')
    ->group(function () {
        Route::resource('vehiculos', VehiculoController::class)->parameters(['vehiculos' => 'vehiculo']);
        Route::patch('vehiculos/{vehiculo}/toggle-activo', [VehiculoController::class, 'toggleActivo'])
            ->name('vehiculos.toggle-activo');

        // Solo lectura: los datos los genera la automatizacion SIMIT (ver
        // POST /api/simit/consultas), no se crean/editan desde la web.
        Route::get('simit-consultas', [SimitConsultaController::class, 'index'])->name('simit-consultas.index');
        Route::get('simit-consultas/{consulta}/screenshot', [SimitConsultaController::class, 'screenshot'])
            ->name('simit-consultas.screenshot');

        Route::get('varadas', [VaradaController::class, 'index'])->name('varadas.index');
        Route::post('varadas', [VaradaController::class, 'store'])->name('varadas.store');
        Route::put('varadas/{varada}', [VaradaController::class, 'update'])->name('varadas.update');
        Route::delete('varadas/{varada}', [VaradaController::class, 'destroy'])->name('varadas.destroy');
        Route::post('varadas/importar', [VaradaController::class, 'importar'])->name('varadas.importar');

        // ── Actas de Entrega a Taller ──────────────────────────────────────────
        Route::get('actas-taller/dashboard', [ActaTallerController::class, 'dashboard'])
            ->name('actas-taller.dashboard');
        Route::get('actas-taller/exportar/excel', [ActaTallerController::class, 'exportarExcel'])
            ->name('actas-taller.exportar-excel');
        Route::get('actas-taller/exportar/pdf', [ActaTallerController::class, 'exportarPdf'])
            ->name('actas-taller.exportar-pdf');
        Route::get('actas-taller', [ActaTallerController::class, 'index'])
            ->name('actas-taller.index');
        Route::get('actas-taller/create', [ActaTallerController::class, 'create'])
            ->name('actas-taller.create');
        Route::post('actas-taller', [ActaTallerController::class, 'store'])
            ->name('actas-taller.store');
        Route::get('actas-taller/{actasTaller}/exportar-pdf', [ActaTallerController::class, 'exportarActaPdf'])
            ->name('actas-taller.exportar-acta-pdf');
        Route::get('actas-taller/{actasTaller}', [ActaTallerController::class, 'show'])
            ->name('actas-taller.show');
        Route::put('actas-taller/{actasTaller}', [ActaTallerController::class, 'update'])
            ->name('actas-taller.update');
        Route::delete('actas-taller/{actasTaller}', [ActaTallerController::class, 'destroy'])
            ->name('actas-taller.destroy');
    });
