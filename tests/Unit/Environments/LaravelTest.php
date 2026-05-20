<?php

namespace Appsignal\Tests\Unit\Environments;

use Appsignal\Config;
use Appsignal\Environments\Laravel;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class LaravelTest extends TestCase
{
    public function testGetConfigReturnsEmptyConfigWhenNoBasePath(): void
    {
        $laravel = new Laravel();
        $config = $laravel->getConfig();

        $this->assertInstanceOf(Config::class, $config);
        $this->assertNull($config->name);
    }

    public function testGetConfigReturnsEmptyConfigWhenNoFile(): void
    {
        $laravel = new Laravel('/nonexistent/path');
        $config = $laravel->getConfig();

        $this->assertInstanceOf(Config::class, $config);
        $this->assertNull($config->name);
    }

    #[RunInSeparateProcess]
    public function testGetConfigLoadsDotenvFile(): void
    {
        $dir = sys_get_temp_dir() . '/appsignal_laravel_test_' . uniqid();
        mkdir($dir);

        file_put_contents($dir . '/.env', "TEST_LARAVEL_ENV_VAR=from_dotenv\n");

        $laravel = new Laravel($dir);
        $laravel->getConfig();

        $this->assertEquals('from_dotenv', $_ENV['TEST_LARAVEL_ENV_VAR']);

        unset($_ENV['TEST_LARAVEL_ENV_VAR']);
        $files = array_diff(scandir($dir), ['.', '..']);
        array_map(fn($f) => unlink("$dir/$f"), $files);
        rmdir($dir);
    }

    public function testGetConfigLoadsFromConfigFile(): void
    {
        $stubPath = __DIR__ . '/../../Stubs/laravel';

        $laravel = new Laravel($stubPath);
        $config = $laravel->getConfig();

        $this->assertEquals('Laravel App', $config->name);
        $this->assertEquals('laravel-key', $config->pushApiKey);
        $this->assertEquals('https://collector.test', $config->collectorEndpoint);
        $this->assertEquals('staging', $config->environment);
        $this->assertEquals(['stack_trace_formatter'], $config->disablePatches);
    }
}
