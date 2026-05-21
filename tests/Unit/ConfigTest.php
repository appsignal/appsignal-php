<?php

namespace Appsignal\Tests\Unit;

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
            $_ENV['APPSIGNAL_COLLECTOR_ENDPOINT'],
            $_ENV['APPSIGNAL_FILTER_ATTRIBUTES'],
            $_ENV['APPSIGNAL_FILTER_FUNCTION_PARAMETERS'],
            $_ENV['APPSIGNAL_FILTER_REQUEST_QUERY_PARAMETERS'],
            $_ENV['APPSIGNAL_FILTER_REQUEST_PAYLOAD'],
            $_ENV['APPSIGNAL_FILTER_REQUEST_SESSION_DATA'],
            $_ENV['APPSIGNAL_IGNORE_ACTIONS'],
            $_ENV['APPSIGNAL_IGNORE_ERRORS'],
            $_ENV['APPSIGNAL_IGNORE_NAMESPACES'],
            $_ENV['APPSIGNAL_IGNORE_LOGS'],
            $_ENV['APPSIGNAL_REQUEST_HEADERS'],
            $_ENV['APPSIGNAL_RESPONSE_HEADERS'],
            $_ENV['APPSIGNAL_SEND_FUNCTION_PARAMETERS'],
            $_ENV['APPSIGNAL_SEND_REQUEST_QUERY_PARAMETERS'],
            $_ENV['APPSIGNAL_SEND_REQUEST_PAYLOAD'],
            $_ENV['APPSIGNAL_SEND_REQUEST_SESSION_DATA'],
            $_ENV['APPSIGNAL_APP_PATH'],
            $_ENV['APPSIGNAL_REVISION'],
            $_ENV['APPSIGNAL_HOSTNAME'],
            $_ENV['APPSIGNAL_SERVICE_NAME'],
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
            collectorEndpoint: 'https://collector.test',
            disablePatches: ['custom_patch'],
            environment: 'production',
            name: 'My App',
            pushApiKey: 'test-key',
        );

        $this->assertEquals('My App', $config->name);
        $this->assertEquals('production', $config->environment);
        $this->assertEquals('test-key', $config->pushApiKey);
        $this->assertEquals('https://collector.test', $config->collectorEndpoint);
        $this->assertEquals(['custom_patch'], $config->disablePatches);
    }

    public function testDefaultsAreNull(): void
    {
        $config = new Config();

        $this->assertConfigIsEmpty($config);
    }

    public function testTryFromFileLoadsConfig(): void
    {
        $config = Config::tryFromFile(__DIR__ . '/../Stubs/laravel/config/appsignal.php');

        $this->assertEquals('Laravel App', $config->name);
        $this->assertEquals('staging', $config->environment);
        $this->assertEquals('laravel-key', $config->pushApiKey);
        $this->assertEquals('https://collector.test', $config->collectorEndpoint);
        $this->assertEquals('/custom/app/path', $config->appPath);
        $this->assertEquals(['stack_trace_formatter'], $config->disablePatches);
    }

    public function testEnvVariableOverridesRevision(): void
    {
        $_ENV['APPSIGNAL_REVISION'] = 'abc123';

        $config = new Config()->applyEnvVariables();

        $this->assertEquals('abc123', $config->revision);
    }

    public function testEnvVariableOverridesHostname(): void
    {
        $_ENV['APPSIGNAL_HOSTNAME'] = 'my-host';

        $config = new Config()->applyEnvVariables();

        $this->assertEquals('my-host', $config->hostname);
    }

    public function testEnvVariableOverridesIgnoreLogs(): void
    {
        $_ENV['APPSIGNAL_IGNORE_LOGS'] = '^done$,Task .* completed successfully';

        $config = new Config()->applyEnvVariables();

        $this->assertEquals(['^done$', 'Task .* completed successfully'], $config->ignoreLogs);
    }

    public function testEnvVariableOverridesServiceName(): void
    {
        $_ENV['APPSIGNAL_SERVICE_NAME'] = 'my-service';

        $config = new Config()->applyEnvVariables();

        $this->assertEquals('my-service', $config->serviceName);
    }

    public function testEnvVariableOverridesAppPath(): void
    {
        $_ENV['APPSIGNAL_APP_PATH'] = '/env/app/path';

        $config = Config::tryFromFile(__DIR__ . '/../Stubs/laravel/config/appsignal.php')->applyEnvVariables();

        $this->assertEquals('/env/app/path', $config->appPath);
    }

    public function testTryFromFileReturnsEmptyConfigForMissingFile(): void
    {
        $config = Config::tryFromFile('/nonexistent/path/appsignal.php');

        $this->assertConfigIsEmpty($config);
    }

    public function testFromFileReturnsEmptyConfigForNonArrayReturn(): void
    {
        $config = Config::tryFromFile(__DIR__ . '/../Stubs/invalid_config.php');

        $this->assertConfigIsEmpty($config);
    }

    public function testFromFileAndEnvVariables(): void
    {
        $_ENV['APPSIGNAL_PUSH_API_KEY'] = 'fake-key';
        $_ENV['APPSIGNAL_COLLECTOR_ENDPOINT'] = 'https://collector.test';
        $_ENV['APPSIGNAL_DISABLE_PATCHES'] = 'foo,bar,baz';

        $config = Config::load(__DIR__ . '/../Stubs/laravel/config/appsignal_partial.php');

        $this->assertEquals('Partial App', $config->name);
        $this->assertEquals('fake-key', $config->pushApiKey);
        $this->assertEquals('https://collector.test', $config->collectorEndpoint);
        $this->assertNull($config->environment);
        $this->assertEquals(['foo', 'bar', 'baz'], $config->disablePatches);
    }

    public function testEnvVariablesOverrideArrayAndBoolOptions(): void
    {
        $_ENV['APPSIGNAL_FILTER_ATTRIBUTES'] = 'foo, bar, baz';
        $_ENV['APPSIGNAL_FILTER_FUNCTION_PARAMETERS'] = 'param1,param2';
        $_ENV['APPSIGNAL_FILTER_REQUEST_QUERY_PARAMETERS'] = 'q,page';
        $_ENV['APPSIGNAL_FILTER_REQUEST_PAYLOAD'] = 'password,token';
        $_ENV['APPSIGNAL_FILTER_REQUEST_SESSION_DATA'] = 'session_id';
        $_ENV['APPSIGNAL_IGNORE_ACTIONS'] = 'HealthController#index';
        $_ENV['APPSIGNAL_IGNORE_ERRORS'] = 'RuntimeException,LogicException';
        $_ENV['APPSIGNAL_IGNORE_NAMESPACES'] = 'background,cron';
        $_ENV['APPSIGNAL_REQUEST_HEADERS'] = 'X-Request-Id,Accept';
        $_ENV['APPSIGNAL_RESPONSE_HEADERS'] = 'Content-Type';
        $_ENV['APPSIGNAL_SEND_FUNCTION_PARAMETERS'] = 'false';
        $_ENV['APPSIGNAL_SEND_REQUEST_QUERY_PARAMETERS'] = 'false';
        $_ENV['APPSIGNAL_SEND_REQUEST_PAYLOAD'] = 'false';
        $_ENV['APPSIGNAL_SEND_REQUEST_SESSION_DATA'] = 'false';

        $config = new Config()->applyEnvVariables();

        $this->assertEquals(['foo', 'bar', 'baz'], $config->filterAttributes);
        $this->assertEquals(['param1', 'param2'], $config->filterFunctionParameters);
        $this->assertEquals(['q', 'page'], $config->filterRequestQueryParameters);
        $this->assertEquals(['password', 'token'], $config->filterRequestPayload);
        $this->assertEquals(['session_id'], $config->filterRequestSessionData);
        $this->assertEquals(['HealthController#index'], $config->ignoreActions);
        $this->assertEquals(['RuntimeException', 'LogicException'], $config->ignoreErrors);
        $this->assertEquals(['background', 'cron'], $config->ignoreNamespaces);
        $this->assertEquals(['X-Request-Id', 'Accept'], $config->requestHeaders);
        $this->assertEquals(['Content-Type'], $config->responseHeaders);
        $this->assertFalse($config->sendFunctionParameters);
        $this->assertFalse($config->sendRequestQueryParameters);
        $this->assertFalse($config->sendRequestPayload);
        $this->assertFalse($config->sendRequestSessionData);
    }

    public function testWithEnvVariables(): void
    {
        $_ENV['APPSIGNAL_PUSH_API_KEY'] = 'fake-key';
        $_ENV['APPSIGNAL_COLLECTOR_ENDPOINT'] = 'https://collector.test';

        $config = new Config();
        $config->applyEnvVariables();

        $this->assertNull($config->name);
        $this->assertNull($config->environment);
        $this->assertEquals('fake-key', $config->pushApiKey);
        $this->assertEquals('https://collector.test', $config->collectorEndpoint);
    }

    public function testExplicitValuesOverEnv(): void
    {
        $_ENV['APPSIGNAL_APP_NAME'] = 'Env App';
        $_ENV['APP_ENV'] = 'staging';
        $_ENV['APPSIGNAL_PUSH_API_KEY'] = 'env-key';
        $_ENV['APPSIGNAL_COLLECTOR_ENDPOINT'] = 'https://collector.env.test';

        $config = new Config(
            name: 'Explicit App',
            environment: 'production',
            pushApiKey: 'explicit-key',
            collectorEndpoint: 'https://collector.explicit.test',
        );

        $this->assertEquals('Explicit App', $config->name);
        $this->assertEquals('production', $config->environment);
        $this->assertEquals('explicit-key', $config->pushApiKey);
        $this->assertEquals('https://collector.explicit.test', $config->collectorEndpoint);
    }

    public function testWithInvalidDisablePatchesConfig(): void
    {
        $config = Config::tryFromFile(__DIR__ . '/../Stubs/laravel/config/appsignal_invalid.php');

        $this->assertEquals([], $config->disablePatches);
    }

    public function testGetMissingFieldsReturnsAllFieldsWhenEmpty(): void
    {
        $config = new Config();

        $this->assertEquals(
            ['push_api_key', 'collector_endpoint', 'name', 'environment'],
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
            ['collector_endpoint', 'environment'],
            $config->getMissingFields(),
        );
    }

    public function testGetMissingFieldsReturnsEmptyArrayWhenValid(): void
    {
        $config = new Config(
            name: 'My App',
            environment: 'production',
            pushApiKey: 'test-key',
            collectorEndpoint: 'https://collector.test',
        );

        $this->assertEmpty($config->getMissingFields());
    }

    protected function assertConfigIsEmpty(Config $config): void
    {
        $this->assertFalse($config->active);
        $this->assertNull($config->name);
        $this->assertNull($config->environment);
        $this->assertNull($config->pushApiKey);
        $this->assertNull($config->collectorEndpoint);
        $this->assertNull($config->appPath);
        $this->assertEmpty($config->disablePatches);
    }
}
