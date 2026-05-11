<?php

namespace Appsignal\Tests\Unit;

use Appsignal\Testing\CapturesTelemetry;
use PHPUnit\Framework\TestCase;

abstract class OpenTelemetryTestCase extends TestCase
{
    use CapturesTelemetry;
}
