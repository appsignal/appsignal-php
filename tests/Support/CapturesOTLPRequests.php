<?php

namespace Appsignal\Tests\Support;

trait CapturesOTLPRequests
{
    protected static int $otlpPort = 8099;
    /** @var resource|null */
    protected static $serverProcess = null;

    public static function startOtlpServer(): void
    {
        self::$serverProcess = proc_open(
            ['php', __DIR__ . '/otlp-server.php', (string) self::$otlpPort],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );

        // Wait for the server to bind
        usleep(100_000);
    }

    public static function stopOtlpServer(): void
    {
        if (self::$serverProcess) {
            proc_terminate(self::$serverProcess);
            proc_close(self::$serverProcess);
        }
    }
}
