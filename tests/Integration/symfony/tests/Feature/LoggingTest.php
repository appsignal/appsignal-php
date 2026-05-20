<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoggingTest extends TestCase
{
    public function test_log(): void
    {
        $this->get('/log');

        $this->assertLogCreated(
            body: 'My log',
            severity: 'INFO',
        );
    }

    public function test_log_with_attributes(): void
    {
        $this->get('/log-with-attributes');

        $this->assertLogCreated(
            severity: 'INFO',
            body: 'My log with attributes',
            attributes: ['context' => ['foo' => 'bar']],
        );
    }
}
