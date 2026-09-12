<?php

return [
    /*
     * Pilar Reparto: desactivado a pedido del cliente (2026-09). Todo el
     * código (rutas, controladores, modelos, sidebar) queda intacto — este
     * flag es el único interruptor. Para reactivarlo: poner esto en `true`
     * (o definir MODULE_REPARTO_ENABLED=true en .env) y en el frontend
     * poner `REPARTO_HABILITADO = true` en resources/js/data/modules.ts.
     */
    'reparto_habilitado' => env('MODULE_REPARTO_ENABLED', false),
];
