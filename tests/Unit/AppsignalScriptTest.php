<?php

namespace Appsignal\Tests\Unit;

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
        $this->assertStringContainsString('validate', $output);
    }

    public function testInitInVanilla(): void
    {
        $projectDir = $this->createProjectDir();

        $output = $this->runScript('init', $projectDir);

        $target = "$projectDir/config/appsignal.php";
        $this->assertStringContainsString("Appsignal config file created at $target", $output);
        $this->assertFileExists($target);
        $this->assertFileEquals(self::$packageDir . '/config-stubs/appsignal.php', $target);
    }

    public function testInitInLaravel(): void
    {
        $projectDir = $this->createProjectDir();
        // these files are used to detect environment
        mkdir("$projectDir/bootstrap");
        touch("$projectDir/artisan");

        $fakeBin = $this->createComposerStub($projectDir);
        $output = $this->runScript('init', $projectDir, $fakeBin);

        $target = "$projectDir/config/appsignal.php";
        $this->assertStringContainsString("Appsignal config file created at $target", $output);
        $this->assertFileExists($target);
        $this->assertFileEquals(self::$packageDir . '/config-stubs/appsignal.laravel.php', $target);
        $this->assertComposerCalled($projectDir, "--working-dir=$projectDir require open-telemetry/opentelemetry-auto-laravel");
    }

    public function testInitInSymfony(): void
    {
        $projectDir = $this->createProjectDir();
        // this file is used to detect environment
        touch("$projectDir/symfony.lock");

        $fakeBin = $this->createComposerStub($projectDir);
        $output = $this->runScript('init', $projectDir, $fakeBin);

        $target = "$projectDir/config/appsignal.php";
        $this->assertStringContainsString("Appsignal config file created at $target", $output);
        $this->assertFileExists($target);
        $this->assertFileEquals(self::$packageDir . '/config-stubs/appsignal.php', $target);
        $this->assertComposerCalled($projectDir, "--working-dir=$projectDir require open-telemetry/opentelemetry-auto-symfony");
    }

    public function testInitInVanillaDoesNotCallComposer(): void
    {
        $projectDir = $this->createProjectDir();

        $fakeBin = $this->createComposerStub($projectDir);
        $this->runScript('init', $projectDir, $fakeBin);

        $this->assertFileDoesNotExist("$projectDir/composer-calls");
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
        mkdir("$projectDir/config", recursive: true);
        file_put_contents("$projectDir/config/appsignal.php", '<?php return [];');

        $output = $this->runScript('init', $projectDir);

        $this->assertStringContainsString('Config file already exists', $output);
        $this->assertEquals('<?php return [];', file_get_contents("$projectDir/config/appsignal.php"));
    }

    public function testInitCreatesConfigDirectoryIfMissing(): void
    {
        $projectDir = $this->createProjectDir();

        $this->assertDirectoryDoesNotExist("$projectDir/config");

        $this->runScript('init', $projectDir);

        $this->assertDirectoryExists("$projectDir/config");
        $this->assertFileExists("$projectDir/config/appsignal.php");
    }

    public function testValidateWithValidConfig(): void
    {
        $projectDir = $this->createProjectDir();
        mkdir("$projectDir/config");
        file_put_contents("$projectDir/config/appsignal.php", "<?php return [
            'name' => 'Test App',
            'environment' => 'test',
            'push_api_key' => 'test-key',
            'collector_endpoint' => 'https://collector.test',
        ];");

        $output = $this->runScript('validate', $projectDir);

        $this->assertStringContainsString('Appsignal config is valid.', $output);
    }

    public function testValidateWithMissingConfig(): void
    {
        $projectDir = $this->createProjectDir();

        $output = $this->runScript('validate', $projectDir);

        $this->assertStringContainsString('Appsignal config is invalid.', $output);
        $this->assertStringContainsString('push_api_key', $output);
        $this->assertStringContainsString('collector_endpoint', $output);
        $this->assertStringContainsString('name', $output);
        $this->assertStringContainsString('environment', $output);
    }

    public function testValidateWithMissingFields(): void
    {
        $projectDir = $this->createProjectDir();
        mkdir("$projectDir/config");
        file_put_contents("$projectDir/config/appsignal.php", "<?php return [
            'name' => 'Test App',
            'environment' => 'test',
        ];");

        $output = $this->runScript('validate', $projectDir);

        $this->assertStringContainsString('Appsignal config is invalid.', $output);
        $this->assertStringContainsString('push_api_key', $output);
        $this->assertStringContainsString('collector_endpoint', $output);
        $this->assertStringNotContainsString('name', $output);
        $this->assertStringNotContainsString('environment', $output);
    }

    public function testValidateInSymfonyChecksConfigPath(): void
    {
        $projectDir = $this->createProjectDir();
        touch("$projectDir/symfony.lock");
        mkdir("$projectDir/config", recursive: true);
        file_put_contents("$projectDir/config/appsignal.php", "<?php return [
            'name' => 'Symfony App',
            'environment' => 'test',
            'push_api_key' => 'test-key',
            'collector_endpoint' => 'https://collector.test',
        ];");

        $output = $this->runScript('validate', $projectDir);

        $this->assertStringContainsString('Appsignal config is valid.', $output);
    }

    protected function runScript(string $command = '', ?string $projectDir = null, ?string $fakeBinDir = null): string
    {
        $script = self::$packageDir . '/bin/appsignal';
        $env = '';

        if ($projectDir !== null) {
            $env = "APPSIGNAL_PROJECT_ROOT=" . escapeshellarg($projectDir)
                . " APPSIGNAL_PACKAGE_DIR=" . escapeshellarg(self::$packageDir)
                . " APPSIGNAL_AUTOLOAD_PATH=" . escapeshellarg(self::$packageDir . '/vendor/autoload.php');
        }

        if ($fakeBinDir !== null) {
            $env .= " PATH=" . escapeshellarg($fakeBinDir) . ':"$PATH"';
        }

        $fullCommand = "$env bash " . escapeshellarg($script) . " $command 2>&1";

        return shell_exec($fullCommand) ?? '';
    }

    protected function createComposerStub(string $projectDir): string
    {
        $binDir = "$projectDir/fake-bin";
        mkdir($binDir);
        $callsFile = escapeshellarg("$projectDir/composer-calls");
        file_put_contents("$binDir/composer", "#!/bin/sh\necho \"\$@\" >> $callsFile\n");
        chmod("$binDir/composer", 0o755);
        return $binDir;
    }

    protected function assertComposerCalled(string $projectDir, string $expectedArgs): void
    {
        $callsFile = "$projectDir/composer-calls";
        $this->assertFileExists($callsFile, 'composer was not called');
        $this->assertStringContainsString($expectedArgs, file_get_contents($callsFile));
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
        self::$packageDir = dirname(dirname(__DIR__));
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
