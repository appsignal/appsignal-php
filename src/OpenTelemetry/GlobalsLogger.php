<?php

namespace Appsignal\OpenTelemetry;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Logs\LoggerInterface;
use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\API\Logs\LogRecordBuilderInterface;
use OpenTelemetry\Context\ContextInterface;

final class GlobalsLogger implements LoggerInterface
{
    /** @param iterable<non-empty-string, mixed> $attributes */
    public function __construct(
        private readonly string $name,
        private readonly ?string $version,
        private readonly ?string $schemaUrl,
        private readonly iterable $attributes,
    ) {}

    public function emit(LogRecord $logRecord): void
    {
        $this->logger()->emit($logRecord);
    }

    public function logRecordBuilder(): LogRecordBuilderInterface
    {
        return $this->logger()->logRecordBuilder();
    }

    public function isEnabled(?ContextInterface $context = null, ?int $severityNumber = null, ?string $eventName = null): bool
    {
        return true;
    }

    private function logger(): LoggerInterface
    {
        return Globals::loggerProvider()
            ->getLogger($this->name, $this->version, $this->schemaUrl, $this->attributes);
    }
}
