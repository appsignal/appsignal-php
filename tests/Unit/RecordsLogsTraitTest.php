<?php

namespace Appsignal\Tests\Unit;

use Appsignal\RecordsLogs;
use Appsignal\Severity;

class RecordsLogsTraitTest extends OpenTelemetryTestCase
{
    public function test_log(): void
    {
        AppsignalWithLogs::log(
            message: "Something's gone terribly wrong here",
            severity: Severity::WARN
        );

        $this->assertLogCreated(
            message: "Something's gone terribly wrong here",
            severity: 'WARN',
        );
    }

    public function test_log_with_custom_logger_name(): void
    {
        AppsignalWithLogs::log(
            message: 'Custom logger message',
            loggerName: 'my-app',
        );

        $this->assertLogCreated(
            message: 'Custom logger message',
            loggerName: 'my-app',
        );
    }

    public function test_log_without_appsignal(): void
    {
        // This removes the providers from CapturesTelemetry trait
        // and should mimic an uninitialized Appsignal state.
        $this->detachScope();

        AppsignalWithLogs::log(
            message: "Should not log this message",
        );

        $this->assertLogNotCreated("Should not log this message");
    }
}

class AppsignalWithLogs
{
    use RecordsLogs;
}
