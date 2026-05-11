<?php

namespace Tests;

use Appsignal\Testing\CapturesTelemetry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class TestCase extends WebTestCase
{
    use CapturesTelemetry;

    protected function get(string $uri): void
    {
        $client = static::createClient();
        $client->request('GET', $uri);
    }

    protected function post(string $uri): void
    {
        $client = static::createClient();
        $client->request('POST', $uri);
    }
}
