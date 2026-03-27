<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoggingTest extends TestCase
{
    public function test_log(): void
    {
        $this->get('/log');

        [$log] = $this->getLogs();

        $this->assertEquals('info', $log->getSeverityText());
        $this->assertEquals('My log', $log->getBody());
        $this->assertNull($log->getEventName());
    }

    public function test_log_with_attributes(): void
    {
        $this->get('/log-with-attributes');

        [$log] = $this->getLogs();

        $this->assertEquals('info', $log->getSeverityText());
        $this->assertEquals('My log with attributes', $log->getBody());
        $this->assertEquals(["foo" => "bar"], $log->getAttributes()->get('context'));
    }
}
