<?php

namespace AppSignal\Integrations\Monolog;

use OpenTelemetry\API\Globals;
use OpenTelemetry\Contrib\Logs\Monolog\Handler as MonologHandler;

class Handler
{
    public static function withLevel(string $level = 'info'): MonologHandler
    {
        $loggerProvider = Globals::loggerProvider();

        return new MonologHandler(loggerProvider: $loggerProvider, level: $level);
    }
}
