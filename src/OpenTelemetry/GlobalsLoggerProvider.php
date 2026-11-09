<?php

namespace Appsignal\OpenTelemetry;

use OpenTelemetry\API\Logs\LoggerInterface;
use OpenTelemetry\API\Logs\LoggerProviderInterface;

final class GlobalsLoggerProvider implements LoggerProviderInterface
{
    /** @param iterable<non-empty-string, mixed> $attributes */
    public function getLogger(
        string $name,
        ?string $version = null,
        ?string $schemaUrl = null,
        iterable $attributes = [],
    ): LoggerInterface {
        return new GlobalsLogger($name, $version, $schemaUrl, $attributes);
    }
}
