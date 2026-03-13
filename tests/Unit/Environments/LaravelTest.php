<?php

namespace AppSignal\Tests\Unit\Environments;

use AppSignal\Config;
use AppSignal\Environments\Laravel;
use PHPUnit\Framework\TestCase;

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

    public function testGetConfigLoadsFromConfigFile(): void
    {
        $stubPath = __DIR__ . '/../../stubs/laravel';

        $laravel = new Laravel($stubPath);
        $config = $laravel->getConfig();

        $this->assertEquals('Laravel App', $config->name);
        $this->assertEquals('laravel-key', $config->pushApiKey);
        $this->assertEquals('https://collector.test', $config->collectorUrl);
        $this->assertEquals('staging', $config->environment);
        $this->assertEquals(['stack_trace_formatter'], $config->disablePatches);
    }
}
