<?php

namespace AppSignal\Tests;

use ArrayObject;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\Context\ScopeInterface;
use OpenTelemetry\SDK\Metrics\Data\Metric;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Metrics\MetricExporter\InMemoryExporter as MetricInMemoryExporter;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Trace\ImmutableSpan;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter as SpanInMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\TestCase;

abstract class OpenTelemetryTestCase extends TestCase
{
    protected ScopeInterface $scope;

    /** @var ArrayObject<int, ImmutableSpan> */
    protected ArrayObject $spanStorage;

    protected TracerProvider $tracerProvider;
    protected MetricInMemoryExporter $metricExporter;
    protected MeterProviderInterface $meterProvider;
    protected ExportingReader $metricReader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->spanStorage = new ArrayObject();
        $this->tracerProvider = new TracerProvider(
            new SimpleSpanProcessor(
                new SpanInMemoryExporter($this->spanStorage),
            ),
        );

        $this->metricExporter = new MetricInMemoryExporter();
        $this->metricReader = new ExportingReader($this->metricExporter);

        $this->meterProvider = MeterProvider::builder()
            ->addReader($this->metricReader)
            ->build();

        $this->scope = Configurator::create()
            ->withTracerProvider($this->tracerProvider)
            ->withMeterProvider($this->meterProvider)
            ->activate();
    }
    /**
     * @return ImmutableSpan
     */
    protected function getLastSpan(): ImmutableSpan
    {
        return $this->spanStorage[$this->spanStorage->count() - 1];
    }

    /**
     * @return Metric[]
     */
    protected function getMetrics(): array
    {
        $this->metricReader->collect();

        return $this->metricExporter->collect();
    }

    protected function findMetricByName(string $name): ?\OpenTelemetry\SDK\Metrics\Data\Metric
    {
        foreach ($this->getMetrics() as $metric) {
            if ($metric->name === $name) {
                return $metric;
            }
        }

        return null;
    }


    /**
     * @return array<string, mixed>
     */
    protected function getLastSpanAttributes(): array
    {
        return $this->getLastSpan()->getAttributes()->toArray();
    }

    protected function tearDown(): void
    {
        $this->scope->detach();
        $this->tracerProvider->shutdown();
        $this->meterProvider->shutdown();

        parent::tearDown();
    }
}
