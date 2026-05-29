<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoggingTest extends TestCase
{
    public function test_log(): void
    {
        $this->get('/log');

        $this->assertLogCreated(
            message: 'My log',
            severity: 'INFO',
        );
    }

    public function test_standalone_log(): void
    {
        $this->get('/standalone-log');

        $this->assertLogCreated(
            message: 'Standalone log',
            severity: 'INFO',
            attributes: ['foo' => 'abc'],
        );
    }

    public function test_log_with_attributes(): void
    {
        $this->get('/log-with-attributes');

        $this->assertLogCreated(
            message: 'My log with attributes',
            severity: 'INFO',
            attributes: ['context' => ['foo' => 'bar']],
        );
    }
}
