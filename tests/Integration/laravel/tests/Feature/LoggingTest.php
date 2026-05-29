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
            severity: 'info'
        );
    }

    public function test_log_with_attributes(): void
    {
        $this->get('/log-with-attributes');

        $this->assertLogCreated(
            message: 'My log with attributes',
            severity: 'info',
            attributes: ['context' => ['foo' => 'bar']],
        );
    }
}
