<?php

namespace Appsignal;

use Appsignal\Severity;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\API\Logs\Severity as OtelSeverity;

trait RecordsLogs
{
    /**
     * @param array<string, mixed> $attributes
     */
    public static function log(
        string $message = '',
        ?Severity $severity = Severity::INFO,
        ?array $attributes = [],
        ?string $loggerName = 'appsignal-php',
    ): void {
        $logger = Globals::loggerProvider()->getLogger($loggerName);

        $otelSeverity = $severity !== null ? OtelSeverity::from($severity->value) : null;

        $logRecord = new LogRecord($message)
            ->setSeverityNumber($otelSeverity)
            ->setSeverityText($severity?->name)
            ->setAttributes($attributes);

        $logger->emit($logRecord);
    }
}
