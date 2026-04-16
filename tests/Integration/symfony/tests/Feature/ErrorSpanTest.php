<?php

namespace Tests\Feature;

use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use Tests\TestCase;

class ErrorSpanTest extends TestCase
{
    public function test_error(): void
    {
        $this->get('/error');

        [, $errorSpan] = $this->getSpans();
        $this->assertEquals('GET /error', $errorSpan->getName());

        $this->assertEquals(SpanKind::KIND_SERVER, $errorSpan->getkind());
        $this->assertEquals(StatusCode::STATUS_ERROR, $errorSpan->getStatus()->getCode());
        [$error] = $errorSpan->getEvents();

        $this->assertEquals('exception', $error->getName());
        $this->assertStringNotContainsString(
            "\tat ",
            $error->getAttributes()->get('exception.stacktrace'),
            'AlignedStackTraceFormatterPatch isn\'t applied',
        );
    }

    public function test_nested_error(): void
    {
        $this->get('/error-nested');

        [, $span] = $this->getSpans();

        $this->assertEquals(SpanKind::KIND_SERVER, $span->getkind());
        $this->assertEquals(StatusCode::STATUS_ERROR, $span->getStatus()->getCode());

        [$event] = $span->getEvents();

        $stacktrace = $event->getAttributes()->get('exception.stacktrace');

        [$firstLine, $secondLine, $thirdLine] = explode(PHP_EOL, $stacktrace);
        $this->assertEquals("Exception: Wrapper", $firstLine);
        $this->assertEquals("src/Bar.php(20): App\Bar::nestedBaz()", $secondLine);
        $this->assertEquals("src/Controller/ErrorsController.php(22): App\Controller\ErrorsController->nested()", $thirdLine);
        $this->assertStringContainsString('Caused by: Exception: Inner', $stacktrace);
    }

    public function test_handled_error(): void
    {
        $this->get('/error-handled');

        [$customSpan] = $this->getSpans();

        $this->assertEquals('handled_error', $customSpan->getName());
        $this->assertEquals(StatusCode::STATUS_ERROR, $customSpan->getStatus()->getCode());

        [$event] = $customSpan->getEvents();

        $stacktrace = $event->getAttributes()->get('exception.stacktrace');

        [$firstLine, $secondLine, $thirdLine] = explode(PHP_EOL, $stacktrace);
        $this->assertEquals("Exception: Wrapper", $firstLine);
        $this->assertEquals("src/Bar.php(20): App\Bar::nestedBaz()", $secondLine);
        $this->assertEquals("src/Controller/ErrorsController.php(30): {closure:App\Controller\ErrorsController::handled():28}()", $thirdLine);
        $this->assertStringContainsString('Caused by: Exception: Inner', $stacktrace);
    }
}
