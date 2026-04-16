<?php

declare(strict_types=1);

namespace Appsignal\Patches;

use OpenTelemetry\SDK\Common\Exception\StackTraceFormatter;
use Throwable;

/**
 * Patches StackTraceFormatter::format() to output "alinged" PHP stack traces.
 *
 * Each PHP stack trace frame shows the code location (caller) and the
 * method that the line is calling(callee), not the method in which the call
 * happens. This makes the stack trace confusing and appear as having an offset
 * between methods called and their locations:
 * ```
 * Exception: Whoops
 * app/Foo.php(12): App\Bar->doSomething()
 * app/Baz.php(21): App\Foo->do()
 * ... more frames
 * ```
 *
 * This method pairs each frame's file/line with the next frame's function,
 * lining up caller's location with the caller function:
 * ```
 * Exception: Whoops
 * app/Bar.php(15): App\Bar->doSomething()
 * app/Foo.php(12): App\Foo->do()
 * app/Baz.php(21): App\Baz::invoke()
 * ```
 */
final class AlignedStackTraceFormatterPatch
{
    public function __construct(
        private ?string $appRoot = "",
    ) {}

    public function __invoke(): void
    {
        $appRoot = $this->appRoot;
        $capturedThrowable = null;

        \OpenTelemetry\Instrumentation\hook(
            StackTraceFormatter::class,
            'format',
            pre: static function (
                string $class,
                array $params,
            ) use (&$capturedThrowable): void {
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
        $lines = [];
        $lines[] = $e::class . ': ' . $e->getMessage();

        $trace = $e->getTrace();
        $frameCount = count($trace);

        // Add class/method/function to the top frame
        $function = $trace[0]['function'] ?? "";
        $class = $trace[0]['class'] ?? "";
        $callType = $trace[0]['type'] ?? "";
        $lines[] = self::formatLine(file: $e->getFile(), line: $e->getLine(), function: $class . $callType . $function);

        // Middle frames
        for ($i = 0; $i < $frameCount - 1; $i++) {
            $file = $trace[$i]['file'] ?? null;
            $line = $trace[$i]['line'] ?? null;
            $function = $trace[$i + 1]['function'] ?? null;
            $class = $trace[$i + 1]['class'] ?? "";
            $callType = $trace[$i + 1]['type'] ?? "";

            if ($file === null || $line === null) {
                continue;
            }

            $lines[] = self::formatLine(file: $file, line: $line, function: $class . $callType . $function);
        }

        // Entry point
        if ($frameCount > 0) {
            $last = $trace[$frameCount - 1];
            $file = $last['file'] ?? null;
            $line = $last['line'] ?? null;

            if ($file !== null && $line !== null) {
                $lines[] = self::formatLine(file: $file, line: $line, function: 'Entry point', addParentheses: false);
            }
        }

        $result = implode(PHP_EOL, $lines);

        if ($appRoot !== null) {
            $result = str_replace($appRoot . '/', '', $result);
        }

        if ($nested) {
            $result = "Caused by: " . static::trimTrace($result);

            $moreLineCount = $frameCount - 2;
            if ($moreLineCount > 0) {
                $result .= PHP_EOL . "... $moreLineCount more";
            }
        }

        $previous = $e->getPrevious();
        if ($previous) {
            $result = $result . PHP_EOL . static::formatTrace($previous, true, $appRoot);
        }

        return $result;
    }

    private static function formatLine(string $file, int $line, ?string $function, bool $addParentheses = true): string
    {
        // For closures, strip the namespace prefix before {closure...}
        if (str_contains($function, '{closure')) {
            $position = strpos($function, '{closure');
            $function = substr($function, $position);
        }

        $call = $addParentheses ? "$function()" : $function;
        return "$file($line): $call";
    }

    public static function trimTrace(string $trace): string
    {
        $lines = explode(PHP_EOL, $trace);
        return implode(PHP_EOL, array_slice($lines, 0, 3));
    }
}
