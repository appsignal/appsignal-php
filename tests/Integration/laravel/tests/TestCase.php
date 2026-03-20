<?php

namespace Tests;

use ArrayObject;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\Context\ScopeInterface;
use OpenTelemetry\SDK\Logs\Exporter\InMemoryExporter as InMemoryLogExporter;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\LoggerProviderInterface;
use OpenTelemetry\SDK\Logs\Processor\SimpleLogRecordProcessor;
use OpenTelemetry\SDK\Logs\ReadableLogRecord;
use OpenTelemetry\SDK\Metrics\Data\Metric;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Metrics\MetricExporter\InMemoryExporter as InMemoryMetricExporter;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Trace\ImmutableSpan;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

abstract class TestCase extends BaseTestCase
{
    protected ScopeInterface $scope;

    /** @var ArrayObject<int, ImmutableSpan> */
    protected ArrayObject $spanStorage;

    protected TracerProvider $tracerProvider;

    /** @var ArrayObject<int, ReadableLogRecord> */
    protected ArrayObject $logStorage;

    protected LoggerProviderInterface $loggerProvider;

    protected InMemoryMetricExporter $metricExporter;

    protected ExportingReader $metricReader;

    protected MeterProviderInterface $meterProvider;

    protected function setUp(): void
    {
        $this->spanStorage = new ArrayObject();
        $this->tracerProvider = new TracerProvider(
            new SimpleSpanProcessor(
                new InMemoryExporter($this->spanStorage),
            ),
        );

        $this->logStorage = new ArrayObject();
        $this->loggerProvider = LoggerProvider::builder()
            ->addLogRecordProcessor(
                new SimpleLogRecordProcessor(
                    new InMemoryLogExporter($this->logStorage),
                ),
            )
            ->build();

        $this->metricExporter = new InMemoryMetricExporter();
        $this->metricReader = new ExportingReader($this->metricExporter);
        $this->meterProvider = MeterProvider::builder()
            ->addReader($this->metricReader)
            ->build();

        $this->scope = Configurator::create()
            ->withTracerProvider($this->tracerProvider)
            ->withLoggerProvider($this->loggerProvider)
            ->withMeterProvider($this->meterProvider)
            ->activate();

        parent::setUp();
    }

    /**
     * @return array<int, ImmutableSpan>
     */
    protected function getSpans(): array
    {
        return array_values((array) $this->spanStorage);
    }

    /**
     * @return array<int, ReadableLogRecord>
     */
    protected function getLogs(): array
    {
        return array_values((array) $this->logStorage);
    }

    /**
     * @return array<int, Metric>
     */
    protected function getMetrics(): array
    {
        $this->metricReader->collect();

        return $this->metricExporter->collect(true);
    }

    protected function tearDown(): void
    {
        $this->scope->detach();
        $this->tracerProvider->shutdown();
        $this->loggerProvider->shutdown();
        $this->meterProvider->shutdown();

        parent::tearDown();
    }
}
