<?php

namespace Appsignal\OpenTelemetry;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\TracerInterface;

final class GlobalsTracer implements TracerInterface
{
    /** @param iterable<non-empty-string, mixed> $attributes */
    public function __construct(
        private readonly string $name,
        private readonly ?string $version,
        private readonly ?string $schemaUrl,
        private readonly iterable $attributes,
    ) {}

    public function spanBuilder(string $spanName): SpanBuilderInterface
    {
        return Globals::tracerProvider()
            ->getTracer($this->name, $this->version, $this->schemaUrl, $this->attributes)
            ->spanBuilder($spanName);
    }

    public function isEnabled(): bool
    {
        return true;
    }
}
