<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$logFile = storage_path('logs/laravel.log');
if (!file_exists($logFile)) { echo "No hay log\n"; exit; }

// Mostrar solo líneas CVD Import de las últimas 200 líneas
$lines = array_slice(file($logFile), -200);
foreach ($lines as $line) {
    if (str_contains($line, 'CVD Import')) {
        echo $line;
    }
}
