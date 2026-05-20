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
        AppsignalStub::instrument(name: 'my-span', closure: fn() => null, spanKind: SpanKind::KIND_SERVER);

        $this->assertCount(1, $this->spanStorage);
        $this->assertSpanCreated(name: 'my-span', spanKind: SpanKind::KIND_SERVER);
    }

    public function testInstrumentWithAttributes(): void
    {
        $activeSpan = AppsignalStub::instrument(
            name: 'my-span',
            attributes: [
                'http.method' => 'GET',
                'http.url' => '/foo',
            ],
        );
        $activeSpan->end();

        $this->assertCount(1, $this->spanStorage);

        $this->assertSpanCreated(
            name: 'my-span',
            attributes: [
                'http.method' => 'GET',
                'http.url' => '/foo',
            ],
        );
    }

    public function testInsturmentWithSpanKind(): void
    {
        $span = AppsignalStub::instrument('should_be_server_span', spanKind: SpanKind::KIND_SERVER);
        $span->end();

        $this->assertCount(1, $this->spanStorage);
        $this->assertSpanCreated(name: 'should_be_server_span', spanKind: SpanKind::KIND_SERVER);
    }

    public function testInstrumentWithClosure(): void
    {
        $called = false;

        AppsignalStub::instrument('closure', closure: function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
        $this->assertCount(1, $this->spanStorage);
        $this->assertSpanCreated(name: 'closure');
    }

    public function testInstrumentWithAttributesAndClosure(): void
    {
        $called = false;

        AppsignalStub::instrument('closure-and-attributes', ['key' => 'value'], closure: function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
        $this->assertCount(1, $this->spanStorage);
        $this->assertSpanCreated(name: 'closure-and-attributes', attributes: ['key' => 'value']);
    }

    public function testInstrumentReturnsActiveSpan(): void
    {
        $result = AppsignalStub::instrument('active-span');

        $this->assertInstanceOf(ActiveSpan::class, $result);

        $result->end();
    }

    public function testInstrumentClosureReceivesOTelSpan(): void
    {
        AppsignalStub::instrument('span', closure: function ($span) {
            $span->setAttribute('from-closure', 'yes');
            $this->assertInstanceOf(Span::class, $span);
        });

        $this->assertSpanCreated(name: 'span', attributes: ['from-closure' => 'yes']);
    }

    public function testInstrumentEndsSpanOnException(): void
    {
        try {
            AppsignalStub::instrument('span-with-error', closure: function () {
                throw new RuntimeException('test error');
            });
        } catch (RuntimeException) {
        }

        $this->assertCount(1, $this->spanStorage);
    }

    public function testRecordError(): void
    {
        $error = new RuntimeException('Something went wrong');

        AppsignalStub::instrument('error-recording-span', closure: function () use ($error) {
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
        AppsignalStub::instrument('some-action', closure: function () {
            AppsignalStub::setAction('UsersController::show');
        });

        $this->assertSpanCreated(name: 'some-action', attributes: ['appsignal.action_name' => 'UsersController::show']);
    }

    public function testSetNamespace(): void
    {
        AppsignalStub::instrument('admin-action', closure: function () {
            AppsignalStub::setNamespace('admin');
        });

        $this->assertSpanCreated(name: 'admin-action', attributes: ['appsignal.namespace' => 'admin']);
    }


    public function testAddCustomData(): void
    {
        AppsignalStub::instrument('some-action', closure: function () {
            AppsignalStub::addAttributes([
                'user_id' => 123456,
                'request_id' => 'abc-123',
            ]);
        });

        $this->assertSpanCreated(name: 'some-action', attributes: ['user_id' => 123456, 'request_id' => 'abc-123']);
    }

    public function testAddTags(): void
    {
        AppsignalStub::instrument('tags-span', closure: function () {
            AppsignalStub::addTags([
                'environment' => 'production',
                'region' => 'eu-west-1',
            ]);
        });

        $this->assertSpanCreated(
            name: 'tags-span',
            attributes: [
                'appsignal.tag.environment' => 'production',
                'appsignal.tag.region' => 'eu-west-1',
            ]
        );
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

        $this->assertSpanCreated(name: 'delegate-span', attributes: ['delegated' => 'yes']);
    }
}

class AppsignalStub
{
    use RecordsInstrumentation;
}
