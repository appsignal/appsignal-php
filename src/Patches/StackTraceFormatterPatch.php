<?php

declare(strict_types=1);

namespace AppSignal\Patches;

use OpenTelemetry\SDK\Common\Exception\StackTraceFormatter;
use Throwable;

use function OpenTelemetry\Instrumentation\hook;

/**
 * Patches StackTraceFormatter::format() to output PHP-native stack traces
 * instead of the default Java-like format, with app root paths stripped.
 */
final class StackTraceFormatterPatch
{
    public function __construct(
        private ?string $appRoot = "",
    ) {}

    public function __invoke(): void
    {
        $appRoot = $this->appRoot;
        $capturedThrowable = null;

        hook(
            StackTraceFormatter::class,
            'format',
            pre: static function (
                string $class,
                array $params,
            ) use (&$capturedThrowable): void {
                // Capture the exception before format() mutates $e via
                // `while ($e = $e->getPrevious())`
                $capturedThrowable = $params[0];
            },
            post: static function (
                string $class,
                array $params,
                string $returnValue,
                ?Throwable $exception,
            ) use (&$capturedThrowable, $appRoot): string {
                /** @var Throwable|null $e */
                $e = $capturedThrowable;
                $capturedThrowable = null;
                if ($e === null) {
                    return $returnValue;
                }
                return self::formatTrace($e, false, $appRoot);
            },
        );
    }

    public static function formatTrace(Throwable $e, ?bool $nested = false, ?string $appRoot = null): string
    {
        $traceString = preg_replace('/^#\d+ /m', '', $e->getTraceAsString());

        $trace = $e::class . ': ' . $e->getMessage() . PHP_EOL . $traceString;

        if ($appRoot !== null) {
            $trace = str_replace($appRoot . '/', '', $trace);
        }

        if ($nested) {
            $trace = "Caused by: " . static::trimTrace($trace);

            $moreLineCount = count($e->getTrace()) - 3;
            if ($moreLineCount > 0) {
                $trace .= PHP_EOL . "... $moreLineCount more";
            }
        }

        $previous = $e->getPrevious();
        if ($previous) {
            $trace = $trace . PHP_EOL . static::formatTrace($previous, true, $appRoot);
        }

        return $trace;
    }

    public static function trimTrace(string $trace): string
    {
        $lines = explode(PHP_EOL, $trace);
        return implode(PHP_EOL, array_slice($lines, 0, 3));
    }
}
