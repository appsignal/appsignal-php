<?php

namespace Appsignal\Tests\Unit;

use Appsignal\Appsignal;
use Appsignal\Patches\StackTraceFormatterPatch;
use Exception;
use OpenTelemetry\SDK\Common\Exception\StackTraceFormatter;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Throwable;

class StackTraceFormatterPatchTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testFormatIncludesClassAndMessage(): void
    {
        (new StackTraceFormatterPatch($this->getPackageRootDir()))();

        $exception = $this->captureException([Thrower::class, 'throwSingle']);

        $result = StackTraceFormatter::format($exception);

        [$firstLine, $secondLine] = explode("\n", $result);

        $this->assertEquals("Appsignal\Tests\Unit\CustomException: Whoops", $firstLine);
        $this->assertStringEndsWith("Appsignal\Tests\Unit\Thrower::throwSingle()", $secondLine);
    }

    #[RunInSeparateProcess]
    public function testFormatStripsAppRoot(): void
    {
        $projectRoot = $this->getPackageRootDir();
        (new StackTraceFormatterPatch($projectRoot))();

        $exception = $this->captureException([Thrower::class, 'throwSingle']);

        $result = StackTraceFormatter::format($exception);
        [,, $thirdLine] = explode(PHP_EOL, $result);

        $this->assertStringNotContainsString($projectRoot . '/', $result);
        $this->assertStringStartsWith('tests/Unit/StackTraceFormatterPatchTest.php', $thirdLine);
    }

    #[RunInSeparateProcess]
    public function testFormatUsesPhpNativeTrace(): void
    {
        (new StackTraceFormatterPatch($this->getPackageRootDir()))();

        $exception = $this->captureException([Thrower::class, 'throwSingle']);

        $result = StackTraceFormatter::format($exception);

        // ensure we aren't using "at Foo.bar(File:line)" Java format
        $this->assertStringNotContainsString("\tat ", $result);
        $this->assertDoesNotMatchRegularExpression('/^#\d+ /m', $result);
    }

    #[RunInSeparateProcess]
    public function testPatchIsSkippedWhenDisabledViaEnv(): void
    {
        $_ENV['APPSIGNAL_DISABLE_PATCHES'] = 'stack_trace_formatter';

        $appSignal = TestableAppsignal::create($this->getPackageRootDir());
        $appSignal->callRegisterGlobalHooks();

        $exception = $this->captureException([Thrower::class, 'throwSingle']);
        $result = StackTraceFormatter::format($exception);

        $this->assertStringContainsString("\tat ", $result);

        unset($_ENV['APPSIGNAL_DISABLE_PATCHES']);
    }

    #[RunInSeparateProcess]
    public function testNestedException(): void
    {
        (new StackTraceFormatterPatch($this->getPackageRootDir()))();

        $exception = $this->captureException([Thrower::class, 'throwNested']);

        $result = StackTraceFormatter::format($exception);

        $this->assertStringStartsWith("Appsignal\Tests\Unit\OuterException: Outer", $result);
        $this->assertStringContainsString("Caused by: Appsignal\Tests\Unit\MiddleException: Middle", $result);
        $this->assertStringContainsString("Caused by: Appsignal\Tests\Unit\InnerException: Inner", $result);
        $this->assertStringNotContainsString("Caused by: Appsignal\Tests\Unit\OuterException: Outer", $result);
    }

    /**
     * @param callable(): mixed $call
     */
    protected function captureException(callable $call): Throwable
    {
        try {
            $call();
        } catch (\Throwable $e) {
            return $e;
        }

        $this->fail("Excpected callable to throw an exception");
    }

    protected function getPackageRootDir(): string
    {
        return dirname(__DIR__, 2);
    }
}

class CustomException extends Exception {};
class InnerException extends Exception {};
class MiddleException extends Exception {};
class OuterException extends Exception {};

class Thrower
{
    public static function throwSingle(): void
    {
        throw new CustomException('Whoops');
    }

    public static function throwNested(): void
    {
        throw new OuterException(
            'Outer',
            0,
            new MiddleException(
                'Middle',
                0,
                new InnerException('Inner')
            )
        );
    }
}

class TestableAppsignal extends Appsignal
{
    public static function create(string $basePath): self
    {
        $instance = new self();
        $instance->setBasePath($basePath);
        return $instance;
    }

    public function callRegisterGlobalHooks(): void
    {
        $this->applyGlobalPatches();
    }
}
