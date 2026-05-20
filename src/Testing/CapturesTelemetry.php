<?php

namespace Appsignal\Testing;

use ArrayObject;
use OpenTelemetry\API\Instrumentation\Configurator;
use PHPUnit\Framework\Assert as PHPUnit;
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
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter as InMemorySpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

trait CapturesTelemetry
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
        parent::setUp();

        $this->spanStorage = new ArrayObject();
        $this->tracerProvider = new TracerProvider(
            new SimpleSpanProcessor(
                new InMemorySpanExporter($this->spanStorage),
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
    }

    protected function tearDown(): void
    {
        $this->scope->detach();
        $this->tracerProvider->shutdown();
        $this->loggerProvider->shutdown();
        $this->meterProvider->shutdown();

        parent::tearDown();
    }

    /**
     * Returns spans in emission order: innermost child first, root span last.
     * Spans close from the inside out — a child span finishes before its parent,
     * so it is emitted and recorded first.
     *
     * Example: [$childSpan, $parentSpan, $rootSpan] = $this->getSpans();
     *
     * @return array<int, ImmutableSpan>
     */
    protected function getSpans(): array
    {
        return array_values((array) $this->spanStorage);
    }

    protected function getLastSpan(): ImmutableSpan
    {
        return $this->spanStorage[$this->spanStorage->count() - 1];
    }

    /** @return array<string, mixed> */
    protected function getLastSpanAttributes(): array
    {
        return $this->getLastSpan()->getAttributes()->toArray();
    }

    /** @return array<int, ReadableLogRecord> */
    protected function getLogs(): array
    {
        return array_values((array) $this->logStorage);
    }

    /** @return Metric[] */
    protected function getMetrics(): array
    {
        $this->metricReader->collect();

        return $this->metricExporter->collect();
    }

    protected function findMetricByName(string $name): ?Metric
    {
        foreach ($this->getMetrics() as $metric) {
            if ($metric->name === $name) {
                return $metric;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $attributes */
    private function findSpan(?string $name = null, array $attributes = [], ?int $spanKind = null): ?ImmutableSpan
    {
        foreach ($this->getSpans() as $span) {
            if ($name !== null && $span->getName() !== $name) {
                continue;
            }

            if ($spanKind !== null && $span->getKind() !== $spanKind) {
                continue;
            }

            foreach ($attributes as $key => $value) {
                if ($span->getAttributes()->get($key) !== $value) {
                    continue 2;
                }
            }

            return $span;
        }

        return null;
    }

    /** @param array<string, mixed> $attributes */
    private function spanCriteriaDescription(?string $name = null, ?array $attributes = [], ?int $spanKind = null): string
    {
        return sprintf(
            '%s%s%s',
            $name !== null ? sprintf(' named "%s"', $name) : '',
            count($attributes) > 0 ? ' with attributes ' . json_encode($attributes) : '',
            $spanKind !== null ? sprintf(' with kind "%s"', $spanKind) : '',
        );
    }

    /** @param array<string, mixed> $attributes */
    protected function assertSpanCreated(?string $name = null, array $attributes = [], ?int $spanKind = null): void
    {
        $spans = $this->getSpans();

        PHPUnit::assertTrue(
            $this->findSpan($name, $attributes, $spanKind) !== null,
            sprintf(
                'No span%s found. Spans: [%s]',
                $this->spanCriteriaDescription($name, $attributes, $spanKind),
                implode(', ', array_map(fn($s) => sprintf('"%s"', $s->getName()), $spans)),
            ),
        );
    }

    /**
     * @param array<string, mixed> $childAttributes
     * @param array<string, mixed> $parentAttributes
     */
    protected function assertSpanIsChildOf(
        ?string $childName = null,
        array $childAttributes = [],
        ?string $parentName = null,
        array $parentAttributes = [],
    ): void {
        $child = $this->findSpan($childName, $childAttributes);
        PHPUnit::assertNotNull(
            $child,
            sprintf('Child span%s not found.', $this->spanCriteriaDescription($childName, $childAttributes)),
        );

        $parent = $this->findSpan($parentName, $parentAttributes);
        PHPUnit::assertNotNull(
            $parent,
            sprintf('Parent span%s not found.', $this->spanCriteriaDescription($parentName, $parentAttributes)),
        );

        PHPUnit::assertSame(
            $parent->getSpanId(),
            $child->getParentSpanId(),
            sprintf(
                'Span%s is not a child of span%s.',
                $this->spanCriteriaDescription($childName, $childAttributes),
                $this->spanCriteriaDescription($parentName, $parentAttributes),
            ),
        );
    }

    /** @param array<string, mixed> $attributes */
    protected function assertLogCreated(?string $body = null, ?string $severity = null, ?array $attributes = []): void
    {
        $logs = $this->getLogs();
        $found = false;

        foreach ($logs as $log) {
            if ($body !== null && $log->getBody() !== $body) {
                continue;
            }

            if ($severity !== null && $log->getSeverityText() !== $severity) {
                continue;
            }

            foreach ($attributes as $key => $value) {
                if ($log->getAttributes()->get($key) !== $value) {
                    continue 2;
                }
            }

            $found = true;
            break;
        }

        PHPUnit::assertTrue($found, sprintf(
            'No log%s%s found. Logs: [%s]',
            $body !== null ? sprintf(' with body "%s"', $body) : '',
            $attributes ? ' with attributes ' . json_encode($attributes) : '',
            implode(', ', array_map(fn($l) => sprintf('"%s"', $l->getBody()), $logs)),
        ));
    }
}
