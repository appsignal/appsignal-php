<?php

use Appsignal\Appsignal;

$recognizedScripts = ['artisan', 'bin/console'];

if (PHP_SAPI == 'cli' && !array_any($recognizedScripts, fn($s) => str_contains($_SERVER['SCRIPT_NAME'] ?? "", $s)) && !isset($_ENV['_APPSIGNAL_TEST'])) {
    return;
}

Appsignal::getInstance()->initialize();
