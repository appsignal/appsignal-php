<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstrumentationTest extends TestCase
{
    public function test_get_span(): void
    {
        $this->get('/');

        $this->assertSpanCreated(name: 'GET /', attributes: [
            'http.request.method' => 'GET',
            'http.route' => '/',
            'http.response.status_code' => 200,
        ]);
    }

    public function test_post_span(): void
    {
        $this->post('/');

        $this->assertSpanCreated(name: 'POST /', attributes: [
            'http.request.method' => 'POST',
            'http.route' => '/',
            'http.response.status_code' => 200,
        ]);
    }

    public function test_appsignal_instrument(): void
    {
        $this->get('/instrument');

        $this->assertSpanCreated(name: 'my-span', attributes: [
            'string-attribute' => 'abcdef',
            'int-attribute' => 1234,
            'bool-attribute' => true,
        ]);
        $this->assertSpanIsChildOf(childName: 'my-span', parentName: 'GET /instrument');
    }

    public function test_appsignal_instrument_nested(): void
    {
        $this->get('/instrument-nested');

        $this->assertSpanCreated(name: 'parent', attributes: ['msg' => 'from parent span']);
        $this->assertSpanCreated(name: 'child', attributes: ['msg' => 'from child span']);
        $this->assertSpanIsChildOf(childName: 'parent', parentName: 'GET /instrument-nested');
        $this->assertSpanIsChildOf(childName: 'child', parentName: 'parent');
    }

    public function test_appsignal_set_action(): void
    {
        $this->get('/set-action');

        $this->assertSpanCreated(name: 'GET /set-action', attributes: ['appsignal.action_name' => 'my action']);
    }

    public function test_add_custom_data(): void
    {
        $this->get('/custom-data');

        $this->assertSpanCreated(name: 'GET /custom-data', attributes: [
            'string-attribute' => 'abcdef',
            'int-attribute' => 1234,
            'bool-attribute' => true,
        ]);
    }

    public function test_appsignal_add_tags(): void
    {
        $this->get('/tags');

        $this->assertSpanCreated(name: 'GET /tags', attributes: [
            'appsignal.tag.string-tag' => 'some value',
            'appsignal.tag.integer-tag' => 1234,
            'appsignal.tag.bool-tag' => true,
        ]);
    }
}
