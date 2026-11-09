<?php

namespace Appsignal\OpenTelemetry;

use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;

final class GlobalsTracerProvider implements TracerProviderInterface
{
    /** @param iterable<non-empty-string, mixed> $attributes */
    public function getTracer(
        string $name,
        ?string $version = null,
        ?string $schemaUrl = null,
        iterable $attributes = [],
    ): TracerInterface {
        return new GlobalsTracer($name, $version, $schemaUrl, $attributes);
    }
}
