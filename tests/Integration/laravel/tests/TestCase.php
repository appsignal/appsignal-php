<?php

namespace Tests;

use Appsignal\Testing\CapturesTelemetry;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CapturesTelemetry;
}
