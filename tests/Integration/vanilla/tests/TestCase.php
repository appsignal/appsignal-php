<?php

namespace Tests;

use App\Router;
use Appsignal\Testing\CapturesTelemetry;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CapturesTelemetry;

    protected function get(string $uri): void
    {
        Router::handle('GET', $uri);
    }

    protected function post(string $uri): void
    {
        Router::handle('POST', $uri);
    }
}
