<?php

/**
 * Registro canónico de submódulos por pilar, usado para el control de acceso
 * granular por usuario (ver App\Support\ModuleAccessRegistry).
 *
 * Debe mantenerse en sincronía manualmente con resources/js/data/modules.ts
 * (ahí vive el equivalente para la navegación del sidebar). Cada clave es el
 * mismo "slug" usado en la URL (/modules/{modulo}/{slug}) y en modules.ts.
 *
 * Cada entrada es:
 *   - un string: la etiqueta, y el submódulo hereda el/los rol(es) dueños
 *     del pilar (más Administrador, que siempre tiene acceso).
 *   - un array ['label' => ..., 'roles' => [...]]: cuando el submódulo lo
 *     pueden ver roles distintos al dueño del pilar — ej. "Colaboradores" es
 *     de Gente pero Seguridad y Flota conservan acceso de solo lectura por
 *     sus propias rutas. 'roles' refleja el conjunto REAL de roles que las
 *     rutas de routes/*.php permiten para ese prefijo (no solo lo que
 *     modules.ts muestra en el sidebar, que puede ser más angosto — ver el
 *     caso de "Plan Premiación" abajo).
 *
 * Los submódulos que no aparecen aquí (páginas internas sin entrada propia
 * en el sidebar, ej. festivos-custom, responsable-ruta) no son
 * personalizables por usuario y siguen controlados solo por el rol general
 * del pilar, como hasta ahora.
 *
 * Nota (puerto desde ADENAR, 2026-10-16): el pilar Reparto está desactivado
 * para este cliente (ver config/modules.php) y modules.ts ya no lo lista, así
 * que no tiene entrada aquí — se agrega cuando se reactive. Por el mismo
 * motivo tampoco hay entrada para "5 Por Qué" (routes/cinco-porques.php),
 * que en ADENAR vive bajo el pilar Reparto: sigue sin middleware
 * submodule.access y controlado solo por `role:`, como antes de esta
 * funcionalidad. EASY tampoco tiene módulo SAC (lo reemplazó "Incentivos")
 * ni los submódulos de Flota "Disponibilidad"/"Carretas"/"Ocupación de
 * Carga" que sí existen en ADENAR.
 */
return [
    'seguridad' => [
        'dispositivos' => 'Dispositivos (Alcoholimetría)',
        'pruebas' => 'Pruebas de Alcoholemia',
        'condiciones-salud' => 'Condiciones de Salud',
        'indicador' => 'Tablero de Indicadores',
        'alertas' => 'Alertas',
        'acis' => 'Reportes ACI',
        'acis-indicadores' => 'Indicadores ACI',
        'acis-consultar-qr' => 'Consultar QR SKAP',
        'evaluaciones-owd' => 'Evaluaciones OWD',
        'evaluaciones-owd-indicadores' => 'Indicadores OWD',
        'evaluaciones-owd-incumplimientos' => 'Incumplimientos OWD',
        'planes-accion-owd' => 'Planes de Acción OWD',
        'evaluaciones-owd-importaciones' => 'Historial de Cargas OWD',
        'examenes-medicos' => 'Exámenes Médicos (Bandeja)',
        'examenes-medicos-indicadores' => 'Indicadores Exámenes Médicos',
        'examenes-medicos-catalogo' => 'Catálogo de Exámenes',
        'examenes-medicos-matriz' => 'Matriz Cargo-Examen',
        'examenes-medicos-conceptos' => 'Conceptos de Aptitud',
        'examenes-medicos-recomendaciones' => 'Catálogo de Recomendaciones',
        'encuestas-morbilidad' => 'Encuestas de Morbilidad',
        'encuestas-morbilidad-preguntas' => 'Catálogo de Preguntas de Morbilidad',
        'glosario' => 'Glosario',
        'rutas-criticas' => 'Mapa de Rutas Críticas',
        'dotacion-epp' => 'Dotación y EPP',
    ],

    'gente' => [
        'colaboradores' => ['label' => 'Colaboradores', 'roles' => ['Gente', 'Seguridad', 'Flota']],
        'correccion-marcaciones' => ['label' => 'Corrección de Marcaciones', 'roles' => ['Gente', 'Seguridad']],
        // La página real (SeguimientoPruebasController::index) se sirve por
        // la ruta genérica /modules/gente/plan-padrinos (ver routes/web.php),
        // protegida solo para el rol Gente; el endpoint alertas-bell de
        // routes/gente.php sí es de lectura amplia (Seguridad/Flota también
        // lo consultan para su propia campanita) — se usa el conjunto más
        // amplio para no bloquear esa sub-vista a quien ya tiene acceso real.
        'plan-padrinos' => ['label' => 'Seguimiento Pruebas y Plan Padrinos', 'roles' => ['Gente', 'Seguridad', 'Flota']],
        'plan-premiacion' => ['label' => 'Plan Premiación', 'roles' => ['Gente', 'Seguridad', 'Flota']],
        'calificaciones' => ['label' => 'Calificaciones', 'roles' => ['Gente', 'Seguridad', 'Flota']],
        'dpo-academy' => ['label' => 'DPO Academy', 'roles' => ['Gente', 'Seguridad', 'Flota']],
        'ausentismo' => ['label' => 'Ausentismo', 'roles' => ['Gente', 'Seguridad', 'Flota']],
        'asistencia-geovictoria' => 'Asistencia GeoVictoria',
        // Cubre tanto "incentivos" (index) como "incentivos/variable": el
        // middleware deriva la clave del primer segmento de la URL tras el
        // módulo, así que ambas vistas comparten esta única entrada (ver
        // submoduleKey() en modules.ts y EnsureSubmoduleAccess en el backend).
        'incentivos' => ['label' => 'Incentivos', 'roles' => ['Gente', 'Seguridad', 'Flota']],
    ],

    'flota' => [
        'vehiculos' => 'Documentación',
        'simit-consultas' => 'Consultas SIMIT',
        'varadas' => 'Control de Varadas',
        'actas-taller' => 'Actas de Taller',
    ],
];
