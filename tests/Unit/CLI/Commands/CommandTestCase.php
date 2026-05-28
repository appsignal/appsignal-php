<?php

namespace Appsignal\Tests\Unit\CLI\Commands;

use PHPUnit\Framework\TestCase;

abstract class CommandTestCase extends TestCase
{
    protected static string $packageDir;
    protected static string $tempDir;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$packageDir = dirname(__DIR__, 4);
        self::$tempDir = sys_get_temp_dir() . '/appsignal_test_' . uniqid();
        mkdir(self::$tempDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob(self::$tempDir . '/*') ?: [] as $entry) {
            exec('rm -rf ' . escapeshellarg($entry));
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        $tempDir = realpath(sys_get_temp_dir());
        $aboutToDeleteDir = realpath(self::$tempDir);
        if ($aboutToDeleteDir && str_starts_with($aboutToDeleteDir, $tempDir)) {
            exec('rm -rf ' . escapeshellarg($aboutToDeleteDir));
        }
        parent::tearDownAfterClass();
    }

    protected function createProjectDir(): string
    {
        $dir = self::$tempDir . '/' . uniqid();
        mkdir($dir, 0o755, true);
        file_put_contents($dir . '/.env', '');
        return $dir;
    }

    /** @param array<string, mixed> $values */
    protected function writeConfigFile(string $projectDir, array $values): void
    {
        if (!is_dir($projectDir . '/config')) {
            mkdir($projectDir . '/config', 0o755, true);
        }
        file_put_contents(
            $projectDir . '/config/appsignal.php',
            '<?php return ' . var_export($values, true) . ';',
        );
    }
}
