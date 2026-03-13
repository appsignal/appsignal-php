<?php

use AppSignal\AppSignal;

// Skip for CLI unless it's artisan
if (PHP_SAPI === 'cli' && !isset($_ENV['LARAVEL_ARTISAN'])) {
	return;
}

if (!AppSignal::extensionIsLoaded()) {
	trigger_error('The "opentelemetry" extension must be loaded to use AppSignal', E_USER_WARNING);

	return;
}

AppSignal::getInstance()->initialize();
