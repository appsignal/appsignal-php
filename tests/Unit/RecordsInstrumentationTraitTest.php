<?php

namespace Appsignal\Tests\Unit;

use Appsignal\ActiveSpan;
use Appsignal\RecordsInstrumentation;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use RuntimeException;

class RecordsInstrumentationTraitTest extends OpenTelemetryTestCase
{
    public function testInstrument(): void
    {
        $activeSpan = AppsignalStub::instrument('my-span');
        $activeSpan->end();

        $this->assertCount(1, $this->spanStorage);
        $this->assertEquals('my-span', $this->getLastSpan()->getName());
        $this->assertEquals(SpanKind::KIND_INTERNAL, $this->getLastSpan()->getKind());
    }

    public function testInstrumentWithAttributes(): void
    {
        $activeSpan = AppsignalStub::instrument('my-span', [
            'http.method' => 'GET',
            'http.url' => '/foo',
        ]);
        $activeSpan->end();

        $this->assertCount(1, $this->spanStorage);

        $attributes = $this->getLastSpanAttributes();
        $this->assertEquals('GET', $attributes['http.method']);
        $this->assertEquals('/foo', $attributes['http.url']);
    }

    public function testInstrumentWithClosure(): void
    {
        $called = false;

        AppsignalStub::instrument('closure', function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
        $this->assertCount(1, $this->spanStorage);
        $this->assertEquals('closure', $this->getLastSpan()->getName());
        $this->assertCount(0, $this->getLastSpanAttributes());
    }

    public function testInstrumentWithAttributesAndClosure(): void
    {
        $called = false;

        AppsignalStub::instrument('closure-and-attributes', ['key' => 'value'], function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
        $this->assertCount(1, $this->spanStorage);
        $this->assertEquals('closure-and-attributes', $this->getLastSpan()->getName());
        $this->assertEquals('value', $this->getLastSpanAttributes()['key']);
    }

    public function testInstrumentReturnsActiveSpan(): void
    {
        $result = AppsignalStub::instrument('active-span');

        $this->assertInstanceOf(ActiveSpan::class, $result);

        $result->end();
    }

    public function testInstrumentClosureReceivesOTelSpan(): void
    {
        AppsignalStub::instrument('span', function ($span) {
            $span->setAttribute('from-closure', 'yes');
            $this->assertInstanceOf(Span::class, $span);
        });

        $attributes = $this->getLastSpanAttributes();
        $this->assertEquals('yes', $attributes['from-closure']);
    }

    public function testInstrumentEndsSpanOnException(): void
    {
        try {
            AppsignalStub::instrument('span-with-error', function () {
                throw new RuntimeException('test error');
            });
        } catch (RuntimeException) {
        }

        $this->assertCount(1, $this->spanStorage);
    }

    public function testRecordError(): void
    {
        $error = new RuntimeException('Something went wrong');

        AppsignalStub::instrument('error-recording-span', function () use ($error) {
            AppsignalStub::setError($error);
        });

        $span = $this->getLastSpan();
        $this->assertEquals(StatusCode::STATUS_ERROR, $span->getStatus()->getCode());
        $this->assertEquals('Something went wrong', $span->getStatus()->getDescription());

        $events = $span->getEvents();
        $this->assertCount(1, $events);
        $this->assertEquals('exception', $events[0]->getName());
    }

    public function testSetAction(): void
    {
        AppsignalStub::instrument('some-action', function () {
            AppsignalStub::setAction('UsersController::show');
        });

        $attributes = $this->getLastSpanAttributes();
        $this->assertEquals('UsersController::show', $attributes['appsignal.action_name']);
    }

    public function testAddCustomData(): void
    {
        AppsignalStub::instrument('some-action', function () {
            AppsignalStub::addAttributes([
                'user_id' => 123456,
                'request_id' => 'abc-123',
            ]);
        });

        $attributes = $this->getLastSpanAttributes();
        $this->assertEquals(123456, $attributes['user_id']);
        $this->assertEquals('abc-123', $attributes['request_id']);
    }

    public function testAddTags(): void
    {
        AppsignalStub::instrument('tags-span', function () {
            AppsignalStub::addTags([
                'environment' => 'production',
                'region' => 'eu-west-1',
            ]);
        });

        $attributes = $this->getLastSpanAttributes();
        $this->assertEquals('production', $attributes['appsignal.tag.environment']);
        $this->assertEquals('eu-west-1', $attributes['appsignal.tag.region']);
    }

    public function testSpanEndDetachesScope(): void
    {
        $activeSpan = AppsignalStub::instrument('scope-span');

        $this->assertInstanceOf(\OpenTelemetry\Context\ScopeInterface::class, $activeSpan->getScope());
        $this->assertInstanceOf(\OpenTelemetry\API\Trace\SpanInterface::class, $activeSpan->getSpan());

        $activeSpan->end();

        $this->assertCount(1, $this->spanStorage);
    }

    public function testActiveSpanDelegatesMethodsToOTelSpan(): void
    {
        $activeSpan = AppsignalStub::instrument('delegate-span');

        $activeSpan->setAttribute('delegated', 'yes');
        $activeSpan->end();

        $attributes = $this->getLastSpanAttributes();
        $this->assertEquals('yes', $attributes['delegated']);
    }
}

class AppsignalStub
{
    use RecordsInstrumentation;
}
