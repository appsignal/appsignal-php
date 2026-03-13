<?php

namespace AppSignal\Tests;

use PHPUnit\Framework\TestCase;

class AppsignalScriptTest extends TestCase
{
    protected static string $packageDir;
    protected static string $tempDir;

    public function testWihtNoArgs(): void
    {
        $output = $this->runScript();

        $this->assertStringContainsString('Usage: appsignal <command>', $output);
        $this->assertStringContainsString('init', $output);
    }

    public function testInitInVanilla(): void
    {
        $projectDir = $this->createProjectDir();

        $output = $this->runScript('init', $projectDir);

        $target = "$projectDir/config/appsignal.php";
        $this->assertStringContainsString("AppSignal config file created at $target", $output);
        $this->assertFileExists($target);
        $this->assertFileEquals(self::$packageDir . '/config-stubs/appsignal.php', $target);
    }

    public function testInitInLaravel(): void
    {
        $projectDir = $this->createProjectDir();
        // these files are used to detect environment
        mkdir("$projectDir/bootstrap");
        touch("$projectDir/artisan");

        $output = $this->runScript('init', $projectDir);

        $target = "$projectDir/config/appsignal.php";
        $this->assertStringContainsString("AppSignal config file created at $target", $output);
        $this->assertFileExists($target);
        $this->assertFileEquals(self::$packageDir . '/config-stubs/appsignal.laravel.php', $target);
    }

    public function testInitInSymfony(): void
    {
        $projectDir = $this->createProjectDir();
        // this file is used to detect environment
        touch("$projectDir/symfony.lock");

        $output = $this->runScript('init', $projectDir);

        $target = "$projectDir/config/packages/appsignal.php";
        $this->assertStringContainsString("AppSignal config file created at $target", $output);
        $this->assertFileExists($target);
        $this->assertFileEquals(self::$packageDir . '/config-stubs/appsignal.php', $target);
    }

    public function testInitSkipsIfConfigAlreadyExists(): void
    {
        $projectDir = $this->createProjectDir();
        mkdir("$projectDir/config", recursive: true);
        file_put_contents("$projectDir/config/appsignal.php", '<?php return [];');

        $output = $this->runScript('init', $projectDir);

        $this->assertStringContainsString('Config file already exists', $output);
        $this->assertEquals('<?php return [];', file_get_contents("$projectDir/config/appsignal.php"));
    }

    public function testInitSkipsIfSymfonyConfigExists(): void
    {
        $projectDir = $this->createProjectDir();
        touch("$projectDir/symfony.lock");
        mkdir("$projectDir/config/packages", recursive: true);
        file_put_contents("$projectDir/config/packages/appsignal.php", '<?php return [];');

        $output = $this->runScript('init', $projectDir);

        $this->assertStringContainsString('Config file already exists', $output);
        $this->assertEquals('<?php return [];', file_get_contents("$projectDir/config/packages/appsignal.php"));
    }

    public function testInitCreatesConfigDirectoryIfMissing(): void
    {
        $projectDir = $this->createProjectDir();

        $this->assertDirectoryDoesNotExist("$projectDir/config");

        $this->runScript('init', $projectDir);

        $this->assertDirectoryExists("$projectDir/config");
        $this->assertFileExists("$projectDir/config/appsignal.php");
    }

    protected function runScript(string $command = '', ?string $projectDir = null): string
    {
        $script = self::$packageDir . '/bin/appsignal';
        $env = '';

        if ($projectDir !== null) {
            $env = "APPSIGNAL_PROJECT_ROOT=" . escapeshellarg($projectDir)
                . " APPSIGNAL_PACKAGE_DIR=" . escapeshellarg(self::$packageDir);
        }

        $fullCommand = "$env bash " . escapeshellarg($script) . " $command 2>&1";

        return shell_exec($fullCommand) ?? '';
    }

    protected function createProjectDir(): string
    {
        $dir = self::$tempDir . '/' . uniqid();
        mkdir($dir, recursive: true);

        return $dir;
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$packageDir = dirname(__DIR__);
        self::$tempDir = sys_get_temp_dir() . '/appsignal_test_' . uniqid();
        mkdir(self::$tempDir, recursive: true);
    }

    protected function tearDown(): void
    {
        // Clean $tempDir between tests, but keep the dir itself
        foreach (glob(self::$tempDir . '/*') as $entry) {
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
}
