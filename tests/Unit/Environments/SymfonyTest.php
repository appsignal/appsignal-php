<?php

namespace Appsignal\Tests\Unit\Environments;

use Appsignal\Config;
use Appsignal\Environments\Symfony;
use PHPUnit\Framework\TestCase;

class SymfonyTest extends TestCase
{
    public function testGetConfigReturnsEmptyConfigWhenNoBasePath(): void
    {
        $symfony = new Symfony();
        $config = $symfony->getConfig();

        $this->assertInstanceOf(Config::class, $config);
        $this->assertNull($config->name);
    }

    public function testGetConfigReturnsEmptyConfigWhenNoFile(): void
    {
        $symfony = new Symfony('/nonexistent/path');
        $config = $symfony->getConfig();

        $this->assertInstanceOf(Config::class, $config);
        $this->assertNull($config->name);
    }

    public function testGetConfigLoadsFromConfigFile(): void
    {
        $stubPath = __DIR__ . '/../../stubs/symfony';

        $symfony = new Symfony($stubPath);
        $config = $symfony->getConfig();

        $this->assertEquals('Symfony App', $config->name);
        $this->assertEquals('symfony-test-key', $config->pushApiKey);
        $this->assertEquals('https://collector-symfony.test', $config->collectorEndpoint);
        $this->assertEquals('staging', $config->environment);
        $this->assertEquals(['stack_trace_formatter'], $config->disablePatches);
    }
}
