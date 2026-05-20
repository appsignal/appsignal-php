<?php

namespace Appsignal;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\API\Logs\Severity;

trait RecordsLogs
{
    /**
     * @param array<string, mixed> $attributes
     */
    public static function log(
        string $body = '',
        ?Severity $severity = Severity::INFO,
        ?array $attributes = [],
        ?string $loggerName = 'appsignal-php',
    ): void {
        $logger = Globals::loggerProvider()->getLogger($loggerName);

        $logRecord = new LogRecord($body)
            ->setSeverityNumber($severity)
            ->setSeverityText($severity->name)
            ->setAttributes($attributes);

        $logger->emit($logRecord);
    }
}
