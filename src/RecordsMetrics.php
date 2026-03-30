<?php

namespace Appsignal;

use OpenTelemetry\API\Globals;

trait RecordsMetrics
{
    /**
     * @param array<string, mixed> $tags
     */
    public static function setGauge(string $name, mixed $value, ?array $tags = []): void
    {
        $meterProvider = Globals::meterProvider();
        $meter = $meterProvider->getMeter('appsignal-php');

        $gauge = $meter->createGauge($name);

        $gauge->record($value, $tags);
    }

    /**
     * @param array<string, mixed> $tags
     */
    public static function addDistributionValue(string $name, mixed $value, ?array $tags = []): void
    {
        $meterProvider = Globals::meterProvider();
        $meter = $meterProvider->getMeter('appsignal-php');

        $histogram = $meter->createHistogram($name);

        $histogram->record($value, $tags);
    }

    /**
     * @param array<string, mixed> $tags
     */
    public static function incrementCounter(string $name, mixed $value, ?array $tags = []): void
    {
        $meterProvider = Globals::meterProvider();
        $meter = $meterProvider->getMeter('appsignal-php');

        $counter = $meter->createCounter($name);

        $counter->add($value, $tags);
    }
}
