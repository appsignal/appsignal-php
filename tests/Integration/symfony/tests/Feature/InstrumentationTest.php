<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstrumentationTest extends TestCase
{
    public function test_get_span(): void
    {
        $this->get('/');

        [$span] = $this->getSpans();

        $this->assertNotNull($span);
        $this->assertEquals("GET /", $span->getName());

        $attributes = $span->getAttributes();
        $this->assertEquals('GET', $attributes->get('http.request.method'));
        $this->assertEquals('/', $attributes->get('http.route'));
        $this->assertEquals(200, $attributes->get('http.response.status_code'));
    }

    public function test_post_span(): void
    {
        $this->post('/');

        [$span] = $this->getSpans();

        $this->assertNotNull($span);
        $this->assertEquals("POST /", $span->getName());

        $attributes = $span->getAttributes();
        $this->assertEquals('POST', $attributes->get('http.request.method'));
        $this->assertEquals('/', $attributes->get('http.route'));
        $this->assertEquals(200, $attributes->get('http.response.status_code'));
    }

    public function test_appsignal_instrument(): void
    {
        $this->get('/instrument');

        [$customSpan, $rootSpan] = $this->getSpans();

        $this->assertEquals("GET /instrument", $rootSpan->getName());
        $this->assertEquals('my-span', $customSpan->getName());
        $this->assertEquals($rootSpan->getSpanId(), $customSpan->getParentSpanId());

        $attributes = $customSpan->getAttributes();
        $this->assertEquals('abcdef', $attributes->get('string-attribute'));
        $this->assertEquals(1234, $attributes->get('int-attribute'));
        $this->assertEquals(true, $attributes->get('bool-attribute'));
    }

    public function test_appsignal_instrument_nested(): void
    {
        $this->get('/instrument-nested');

        [$childSpan, $parentSpan, $rootSpan] = $this->getSpans();

        $this->assertEquals("GET /instrument-nested", $rootSpan->getName());

        $this->assertEquals('parent', $parentSpan->getName());
        $this->assertEquals($rootSpan->getSpanId(), $parentSpan->getParentSpanId());
        $this->assertEquals('from parent span', $parentSpan->getAttributes()->get('msg'));

        $this->assertEquals('child', $childSpan->getName());
        $this->assertEquals($parentSpan->getSpanId(), $childSpan->getParentSpanId());
        $this->assertEquals('from child span', $childSpan->getAttributes()->get('msg'));
    }

    public function test_appsignal_set_action(): void
    {
        $this->get('/set-action');

        [$span] = $this->getSpans();
        $this->assertEquals("GET /set-action", $span->getName());
        $this->assertEquals('my action', $span->getAttributes()->get('appsignal.action_name'));
    }

    public function test_add_custom_data(): void
    {
        $this->get('/custom-data');

        [$span] = $this->getSpans();
        $this->assertEquals("GET /custom-data", $span->getName());
        $this->assertEquals('abcdef', $span->getAttributes()->get('string-attribute'));
        $this->assertEquals(1234, $span->getAttributes()->get('int-attribute'));
        $this->assertEquals(true, $span->getAttributes()->get('bool-attribute'));
    }

    public function test_appsignal_add_tags(): void
    {
        $this->get('/tags');

        $spans = $this->getSpans();

        $span = $spans[0];
        $this->assertNotNull($span);
        $this->assertEquals("GET /tags", $span->getName());

        $attributes = $span->getAttributes();

        $this->assertEquals('some value', $attributes->get('appsignal.tag.string-tag'));
        $this->assertEquals(1234, $attributes->get('appsignal.tag.integer-tag'));
        $this->assertEquals(true, $attributes->get('appsignal.tag.bool-tag'));
    }
}
