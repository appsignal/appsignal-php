<?php

namespace Appsignal;

use Closure;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;
use Throwable;

trait RecordsInstrumentation
{
    /**
     * @param array<string, mixed>|Closure $attributesOrClosure
     */
    public static function instrument(string $name, array|Closure $attributesOrClosure = [], ?Closure $closure = null): ActiveSpan
    {
        $tracer = Globals::tracerProvider()->getTracer('appsignal-php');

        $span = $tracer->spanBuilder($name)
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();

        $scope = $span->activate();

        if (is_array($attributesOrClosure)) {
            foreach ($attributesOrClosure as $key => $value) {
                $span->setAttribute($key, $value);
            }
        }

        $cb = null;

        if (is_callable($attributesOrClosure)) {
            $cb = $attributesOrClosure;
        }

        if (is_callable($closure)) {
            $cb = $closure;
        }

        if (!is_null($cb)) {
            try {
                $cb($span);
            } finally {
                $span->end();
                $scope->detach();
            }
        }

        return new ActiveSpan(span: $span, scope: $scope);
    }

    public static function setError(Throwable $error): void
    {
        $span = Span::getCurrent();

        $span->recordException($error);
        $span->setStatus(StatusCode::STATUS_ERROR, $error->getMessage());
    }

    public static function setAction(string $name): void
    {
        $span = Span::getCurrent();
        $span->setAttribute('appsignal.action_name', $name);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function addAttributes(array $data): void
    {
        $span = Span::getCurrent();

        foreach ($data as $key => $value) {
            $span->setAttribute($key, $value);
        }
    }

    /**
     * @param array<string, mixed> $tags
     */
    public static function addTags(array $tags): void
    {
        $span = Span::getCurrent();

        foreach ($tags as $key => $value) {
            $span->setAttribute("appsignal.tag.$key", $value);
        }
    }
}


/**
 * @mixin SpanInterface
 *
 * @method \OpenTelemetry\API\Trace\SpanContextInterface getContext()
 * @method bool isRecording()
 * @method self setAttribute(string $key, bool|int|float|string|array<mixed>|null $value)
 * @method self setAttributes(iterable<string, mixed> $attributes)
 * @method self addLink(\OpenTelemetry\API\Trace\SpanContextInterface $context, iterable<string, mixed> $attributes = [])
 * @method self addEvent(string $name, iterable<string, mixed> $attributes = [], ?int $timestamp = null)
 * @method self recordException(\Throwable $exception, iterable<string, mixed> $attributes = [])
 * @method self updateName(string $name)
 * @method self setStatus(string $code, ?string $description = null)
 * @method self reportError(Throwable $e)
 */
class ActiveSpan
{
    public function __construct(
        protected SpanInterface $span,
        protected ScopeInterface $scope,
    ) {}

    public function end(): void
    {
        $this->scope->detach();
        $this->span->end();
    }

    public function getScope(): ScopeInterface
    {
        return $this->scope;
    }

    public function getSpan(): SpanInterface
    {
        return $this->span;
    }

    /**
     * @param array<int,mixed> $args
     */
    public function __call(string $name, array $args): mixed
    {
        return $this->span->$name(...$args);
    }
}
