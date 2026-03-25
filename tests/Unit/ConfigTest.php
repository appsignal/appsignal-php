<?php

namespace Appsignal\Tests\unit;

use Appsignal\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalEnv = $_ENV;

        unset(
            $_ENV['APPSIGNAL_APP_NAME'],
            $_ENV['APP_NAME'],
            $_ENV['APP_ENV'],
            $_ENV['APPSIGNAL_PUSH_API_KEY'],
            $_ENV['APPSIGNAL_COLLECTOR_URL'],
        );
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnv;
        parent::tearDown();
    }

    public function testConstructorSetsProperties(): void
    {
        $config = new Config(
            collectorUrl: 'https://collector.test',
            disablePatches: ['custom_patch'],
            environment: 'production',
            name: 'My App',
            pushApiKey: 'test-key',
        );

        $this->assertEquals('My App', $config->name);
        $this->assertEquals('production', $config->environment);
        $this->assertEquals('test-key', $config->pushApiKey);
        $this->assertEquals('https://collector.test', $config->collectorUrl);
        $this->assertEquals(['custom_patch'], $config->disablePatches);
    }

    public function testDefaultsAreNull(): void
    {
        $config = new Config();

        $this->assertConfigIsEmpty($config);
    }

    public function testTryFromFileLoadsConfig(): void
    {
        $config = Config::tryFromFile(__DIR__ . '/../stubs/laravel/config/appsignal.php');

        $this->assertEquals('Laravel App', $config->name);
        $this->assertEquals('staging', $config->environment);
        $this->assertEquals('laravel-key', $config->pushApiKey);
        $this->assertEquals('https://collector.test', $config->collectorUrl);
        $this->assertEquals(['stack_trace_formatter'], $config->disablePatches);
    }

    public function testTryFromFileReturnsEmptyConfigForMissingFile(): void
    {
        $config = Config::tryFromFile('/nonexistent/path/appsignal.php');

        $this->assertConfigIsEmpty($config);
    }

    public function testFromFileReturnsEmptyConfigForNonArrayReturn(): void
    {
        $config = Config::tryFromFile(__DIR__ . '/../stubs/invalid_config.php');

        $this->assertConfigIsEmpty($config);
    }

    public function testFromFileAndEnvVariables(): void
    {
        $_ENV['APPSIGNAL_PUSH_API_KEY'] = 'fake-key';
        $_ENV['APPSIGNAL_COLLECTOR_URL'] = 'https://collector.test';
        $_ENV['APPSIGNAL_DISABLE_PATCHES'] = 'foo,bar,baz';

        $config = Config::tryFromFile(__DIR__ . '/../stubs/laravel/config/appsignal_partial.php');

        $this->assertEquals('Partial App', $config->name);
        $this->assertEquals('fake-key', $config->pushApiKey);
        $this->assertEquals('https://collector.test', $config->collectorUrl);
        $this->assertNull($config->environment);
        $this->assertEquals(['foo', 'bar', 'baz'], $config->disablePatches);
    }

    public function testWithEnvVariables(): void
    {
        $_ENV['APPSIGNAL_PUSH_API_KEY'] = 'fake-key';
        $_ENV['APPSIGNAL_COLLECTOR_URL'] = 'https://collector.test';

        $config = new Config();

        $this->assertNull($config->name);
        $this->assertNull($config->environment);
        $this->assertEquals('fake-key', $config->pushApiKey);
        $this->assertEquals('https://collector.test', $config->collectorUrl);
    }

    public function testExplicitValuesOverEnv(): void
    {
        $_ENV['APPSIGNAL_APP_NAME'] = 'Env App';
        $_ENV['APP_ENV'] = 'staging';
        $_ENV['APPSIGNAL_PUSH_API_KEY'] = 'env-key';
        $_ENV['APPSIGNAL_COLLECTOR_URL'] = 'https://collector.env.test';

        $config = new Config(
            name: 'Explicit App',
            environment: 'production',
            pushApiKey: 'explicit-key',
            collectorUrl: 'https://collector.explicit.test',
        );

        $this->assertEquals('Explicit App', $config->name);
        $this->assertEquals('production', $config->environment);
        $this->assertEquals('explicit-key', $config->pushApiKey);
        $this->assertEquals('https://collector.explicit.test', $config->collectorUrl);
    }

    public function testWithInvalidDisablePatchesConfig(): void
    {
        $config = Config::tryFromFile(__DIR__ . '/../stubs/laravel/config/appsignal_invalid.php');

        $this->assertEquals([], $config->disablePatches);
    }

    public function testGetMissingFieldsReturnsAllFieldsWhenEmpty(): void
    {
        $config = new Config();

        $this->assertEquals(
            ['push_api_key', 'collector_url', 'name', 'environment'],
            $config->getMissingFields(),
        );
    }

    public function testGetMissingFieldsReturnsOnlyMissingFields(): void
    {
        $config = new Config(
            name: 'My App',
            pushApiKey: 'test-key',
        );

        $this->assertEquals(
            ['collector_url', 'environment'],
            $config->getMissingFields(),
        );
    }

    public function testGetMissingFieldsReturnsEmptyArrayWhenValid(): void
    {
        $config = new Config(
            name: 'My App',
            environment: 'production',
            pushApiKey: 'test-key',
            collectorUrl: 'https://collector.test',
        );

        $this->assertEmpty($config->getMissingFields());
    }

    protected function assertConfigIsEmpty(Config $config): void
    {
        $this->assertNull($config->name);
        $this->assertNull($config->environment);
        $this->assertNull($config->pushApiKey);
        $this->assertNull($config->collectorUrl);
        $this->assertEmpty($config->disablePatches);
    }
}
