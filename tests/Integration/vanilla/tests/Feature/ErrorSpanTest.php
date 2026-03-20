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

        [$errorSpan] = $this->getSpans();
        $this->assertEquals('GET /error', $errorSpan->getName());

        $this->assertEquals(SpanKind::KIND_SERVER, $errorSpan->getkind());
        $this->assertEquals(StatusCode::STATUS_ERROR, $errorSpan->getStatus()->getCode());
        [$error] = $errorSpan->getEvents();

        $this->assertEquals('exception', $error->getName());
        $this->assertStringNotContainsString(
            "\tat ",
            $error->getAttributes()->get('exception.stacktrace'),
            'StackTraceFormatterPatch isn\'t applied',
        );
    }

    public function test_nested_error(): void
    {
        $this->get('/error-nested');

        [$span] = $this->getSpans();

        $this->assertEquals(SpanKind::KIND_SERVER, $span->getkind());
        $this->assertEquals(StatusCode::STATUS_ERROR, $span->getStatus()->getCode());

        [$event] = $span->getEvents();

        $stacktrace = $event->getAttributes()->get('exception.stacktrace');

        [$firstLine, $secondLine] = explode(PHP_EOL, $stacktrace);
        $this->assertEquals("Exception: Wrapper", $firstLine);
        $this->assertEquals("src/ErrorsController.php(14): App\Bar::nestedBaz()", $secondLine);
        $this->assertStringContainsString('Caused by: Exception: Inner', $stacktrace);
    }
}
