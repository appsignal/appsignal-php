<?php

namespace Appsignal\Tests\Unit;

use Appsignal\Patches\AlignedStackTraceFormatterPatch;
use Appsignal\Tests\Stubs\BadClass;
use Appsignal\Tests\Stubs\Catcher;
use OpenTelemetry\SDK\Common\Exception\StackTraceFormatter;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class AlignedStackTraceFormatterPatchTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testTopFrameIsTheThrowLocation(): void
    {
        (new AlignedStackTraceFormatterPatch($this->getPackageRootDir()))();

        $exception = Catcher::callAndCapture([BadClass::class, 'throw']);

        $result = StackTraceFormatter::format($exception);
        [$first_line, $second_line] = explode(PHP_EOL, $result);

        $this->assertEquals("Appsignal\Tests\Stubs\RootCauseException: Root cause!", $first_line);

        // Second line should be where the throw happened, with "throw()"
        $this->assertStringContainsString('throw()', $second_line);
        $this->assertStringContainsString('BadClass.php', $second_line);
    }

    #[RunInSeparateProcess]
    public function testFunctionNameAlignsWithLocationInFrame(): void
    {
        (new AlignedStackTraceFormatterPatch($this->getPackageRootDir()))();

        $exception = Catcher::callAndCapture([BadClass::class, 'throw']);

        $result = StackTraceFormatter::format($exception);
        [$first, $second, $third] = explode(PHP_EOL, $result);

        $this->assertStringContainsString("Appsignal\Tests\Stubs\RootCauseException: Root cause!", $first);

        $this->assertStringContainsString("BadClass.php(11):", $second);
        $this->assertStringContainsString("Appsignal\Tests\Stubs\BadClass::throw()", $second);

        $this->assertStringContainsString("Catcher.php(15):", $third);
        $this->assertStringContainsString("Appsignal\Tests\Stubs\Catcher::callAndCapture()", $third);
    }

    #[RunInSeparateProcess]
    public function testFrameFormatIsFileLineFunction(): void
    {
        (new AlignedStackTraceFormatterPatch($this->getPackageRootDir()))();

        $exception = Catcher::callAndCapture([BadClass::class, 'throw']);

        $result = StackTraceFormatter::format($exception);
        $lines = explode(PHP_EOL, $result);

        // Skip the first line (exception message), check frame format
        for ($i = 1; $i < count($lines); $i++) {
            $this->assertMatchesRegularExpression(
                '/^.+\(\d+\): .+$/',
                $lines[$i],
                "Frame should match 'file(line): function()' format"
            );
        }
    }

    #[RunInSeparateProcess]
    public function testFunctionNamesHaveNamespace(): void
    {
        (new AlignedStackTraceFormatterPatch($this->getPackageRootDir()))();

        $exception = Catcher::callAndCapture([BadClass::class, 'throw']);

        $result = StackTraceFormatter::format($exception);
        [, $second, $third] = explode(PHP_EOL, $result);

        $this->assertStringContainsString("Appsignal\Tests\Stubs\BadClass", $second);
        $this->assertStringContainsString("Appsignal\Tests\Stubs\Catcher", $third);
    }

    #[RunInSeparateProcess]
    public function testFormatHandlesClosures(): void
    {
        (new AlignedStackTraceFormatterPatch($this->getPackageRootDir()))();

        $exception = Catcher::callAndCapture([BadClass::class, 'throwInClosure']);


        $result = StackTraceFormatter::format($exception);
        [,, $third] = explode(PHP_EOL, $result);

        $this->assertStringContainsString("BadClass.php(30): {closure:Appsignal\Tests\Stubs\BadClass::throwInClosure():29}()", $third);
    }

    #[RunInSeparateProcess]
    public function testFormatIgnoresArgs(): void
    {
        (new AlignedStackTraceFormatterPatch($this->getPackageRootDir()))();

        $exception = Catcher::callAndCapture(fn() => BadClass::throwWithSecrets("abcdef"));

        $trace = StackTraceFormatter::format($exception);

        $this->assertStringNotContainsString("abcdef", $trace);
    }

    #[RunInSeparateProcess]
    public function testFormatIgnoresArgsInClosures(): void
    {
        (new AlignedStackTraceFormatterPatch($this->getPackageRootDir()))();

        $exception = Catcher::callAndCapture(fn() => BadClass::throwWithSecretsInClosure("password"));

        $trace = StackTraceFormatter::format($exception);

        $this->assertStringNotContainsString("password", $trace);
    }

    #[RunInSeparateProcess]
    public function testFormatStripsAppRoot(): void
    {
        $projectRoot = $this->getPackageRootDir();
        (new AlignedStackTraceFormatterPatch($projectRoot))();

        $exception = Catcher::callAndCapture([BadClass::class, 'throw']);

        $result = StackTraceFormatter::format($exception);

        $this->assertStringNotContainsString($projectRoot . '/', $result);
    }

    #[RunInSeparateProcess]
    public function testLastFrameIsEntryPoint(): void
    {
        (new AlignedStackTraceFormatterPatch($this->getPackageRootDir()))();

        $exception = Catcher::callAndCapture([BadClass::class, 'throw']);

        $result = StackTraceFormatter::format($exception);
        $lines = explode(PHP_EOL, $result);
        $lastLine = end($lines);

        $this->assertStringContainsString('Entry point', $lastLine);
    }

    #[RunInSeparateProcess]
    public function testNestedException(): void
    {
        (new AlignedStackTraceFormatterPatch($this->getPackageRootDir()))();

        $exception = Catcher::callAndCapture([BadClass::class, 'throwNested']);

        $result = StackTraceFormatter::format($exception);

        $this->assertStringStartsWith("Appsignal\Tests\Stubs\OuterWrapperException: Outer", $result);
        $this->assertStringContainsString("Caused by: Appsignal\Tests\Stubs\MiddleWrapperException: Middle", $result);
        $this->assertStringContainsString("Caused by: Appsignal\Tests\Stubs\InnerWrapperException: Inner", $result);
    }

    #[RunInSeparateProcess]
    public function testPatchReplacesJaveLikeStackTrace(): void
    {
        (new AlignedStackTraceFormatterPatch($this->getPackageRootDir()))();

        $exception = Catcher::callAndCapture([BadClass::class, 'throw']);

        $result = StackTraceFormatter::format($exception);

        $this->assertStringNotContainsString("\tat ", $result);
        $this->assertDoesNotMatchRegularExpression('/^#\d+ /m', $result);
    }

    protected function getPackageRootDir(): string
    {
        return dirname(__DIR__, 2);
    }
}
