<?php

use Appsignal\Appsignal;

// Skip for CLI unless it's artisan or integration tests
if (PHP_SAPI === 'cli' && !isset($_ENV['LARAVEL_ARTISAN']) && !isset($_ENV['_APPSIGNAL_TEST'])) {
    return;
}

if (!Appsignal::extensionIsLoaded()) {
    trigger_error('The "opentelemetry" extension must be loaded to use Appsignal', E_USER_WARNING);

    return;
}

Appsignal::getInstance()->initialize();
