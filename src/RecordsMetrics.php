<?php

namespace AppSignal;

use OpenTelemetry\API\Globals;

trait RecordsMetrics
{
    public static function setGauge(string $name, mixed $value, ?array $tags = []): void
    {
        $meterProvider = Globals::meterProvider();
        $meter = $meterProvider->getMeter('my-app-name');

        $gauge = $meter->createGauge($name);

        $gauge->record($value, $tags);
    }

    public static function addDistributionValue(string $name, mixed $value, ?array $tags = []): void
    {
        $meterProvider = Globals::meterProvider();
        $meter = $meterProvider->getMeter('my-app-name');

        $histogram = $meter->createHistogram($name);

        $histogram->record($value, $tags);
    }

    public static function incrementCounter(string $name, mixed $value, ?array $tags = []): void
    {
        $meterProvider = Globals::meterProvider();
        $meter = $meterProvider->getMeter('my-app-name');

        $counter = $meter->createCounter($name);

        $counter->add($value, $tags);
    }
}
