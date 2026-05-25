<?php

namespace Appsignal\Integrations\Monolog;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Logs as API;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Contrib\Logs\Monolog\Handler as MonologHandler;

class Handler extends MonologHandler
{
    /** @var array<string, LoggerProxy> */
    private array $proxies = [];

    public static function withLevel(string $level = 'info'): self
    {
        $loggerProvider = Globals::loggerProvider();

        return new self(loggerProvider: $loggerProvider, level: $level);
    }

    protected function getLogger(string $channel): API\LoggerInterface
    {
        if (!array_key_exists($channel, $this->proxies)) {
            $this->proxies[$channel] = new LoggerProxy(parent::getLogger($channel), $channel);
        }

        return $this->proxies[$channel];
    }
}

class LoggerProxy implements API\LoggerInterface
{
    public function __construct(
        private readonly API\LoggerInterface $logger,
        private readonly string $channel,
    ) {}

    public function emit(API\LogRecord $logRecord): void
    {
        $logRecord->setAttribute('appsignal.group', $this->channel);
        $this->logger->emit($logRecord);
    }

    public function logRecordBuilder(): API\LogRecordBuilderInterface
    {
        return $this->logger->logRecordBuilder();
    }

    public function isEnabled(?ContextInterface $context = null, ?int $severityNumber = null, ?string $eventName = null): bool
    {
        return $this->logger->isEnabled($context, $severityNumber, $eventName);
    }
}
