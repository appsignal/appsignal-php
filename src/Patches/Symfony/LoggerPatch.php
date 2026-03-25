<?php

namespace Appsignal\Patches\Symfony;

use Monolog\Logger;
use OpenTelemetry\API\Globals;
use OpenTelemetry\Contrib\Logs\Monolog\Handler;
use Psr\Log\LogLevel;

use function OpenTelemetry\Instrumentation\hook;

final class LoggerPatch
{
    public function __invoke(): void
    {
        hook(
            Logger::class,
            '__construct',
            post: static function (Logger $logger): void {
                if ($logger->getName() !== 'app') {
                    return;
                }

                $loggerProvider = Globals::loggerProvider();
                $handler = new Handler($loggerProvider, LogLevel::INFO);
                $logger->pushHandler($handler);
            },
        );
    }
}
