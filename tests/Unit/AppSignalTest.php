<?php

namespace AppSignal\Tests\Unit;

use AppSignal\AppSignal;
use AppSignal\Config;
use AppSignal\Environments\Environment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class AppSignalTest extends TestCase
{
    use CapturesWarnings;

    protected function tearDown(): void
    {
        AppSignal::setInstance(null);
        $this->cleanupFixtures();
    }

    public function testClassIsSingleton(): void
    {
        $first = AppSignal::getInstance();
        $second = AppSignal::getInstance();

        $this->assertSame($first, $second);
    }

    public function testSetInstanceReplacesInstance(): void
    {
        $original = AppSignal::getInstance();

        AppSignal::setInstance(null);

        $new = AppSignal::getInstance();

        $this->assertNotSame($original, $new);
    }

    public function testBasePathGetterAndSetter(): void
    {
        $appSignal = AppSignal::getInstance();

        // default should be null
        $this->assertNull($appSignal->getBasePath());

        $appSignal->setBasePath('/some/path');

        $this->assertEquals('/some/path', $appSignal->getBasePath());

        // can reset by setting to null
        $appSignal->setBasePath(null);

        $this->assertNull($appSignal->getBasePath());
    }

    public function testExtensionIsLoadedReturnsBool(): void
    {
        $result = AppSignal::extensionIsLoaded();

        $this->assertSame(extension_loaded('opentelemetry'), $result);
    }

    public function testLoadEnvFromDotenvFile(): void
    {
        $dir = $this->createTempDir();
        file_put_contents($dir . '/.env', "TEST_APPSIGNAL_VAR=hello_world\n");

        unset($_ENV['APP_KEY']);

        $appSignal = AppSignal::getInstance();
        $appSignal->setBasePath($dir);

        $appSignal->loadEnv();

        $this->assertEquals('hello_world', $_ENV['TEST_APPSIGNAL_VAR']);

        unset($_ENV['TEST_APPSIGNAL_VAR']);
    }

    public function testLoadEnvSkipsIfAppKeyAlreadySet(): void
    {
        $dir = $this->createTempDir();
        file_put_contents($dir . '/.env', "TEST_KEY=should_not_load\n");

        $_ENV['APP_KEY'] = 'some-key';

        $appSignal = AppSignal::getInstance();
        $appSignal->setBasePath($dir);

        $appSignal->loadEnv();

        $this->assertArrayNotHasKey('TEST_KEY', $_ENV);

        unset($_ENV['APP_KEY']);
    }

    public function testLoadEnvSkipsIfBasePathIsNull(): void
    {
        unset($_ENV['APP_KEY']);

        $appSignal = AppSignal::getInstance();

        $appSignal->loadEnv();

        $this->assertNull($appSignal->getBasePath());
    }

    #[RunInSeparateProcess]
    public function testGetRevisionFromGitReturnsCommitHash(): void
    {
        $dir = $this->createTempDir();
        exec("git -C " . escapeshellarg($dir) . " init 2>/dev/null");
        exec("git -C " . escapeshellarg($dir) . " -c user.name=Test -c user.email=test@test.com -c commit.gpgsign=false commit --allow-empty -m 'init' 2>/dev/null");

        $appSignal = AppSignal::getInstance();
        $appSignal->setBasePath($dir);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $appSignal->getRevision());
    }

    public function testGetRevisionReturnsUnknownWhenBasePathIsNull(): void
    {
        $appSignal = AppSignal::getInstance();
        $appSignal->setBasePath(null);

        $this->assertEquals('unknown', $appSignal->getRevision());
    }

    public function testGetRevisionReturnsUnknownForNonGitDirectory(): void
    {
        $fixtureDir = $this->createTempDir();

        $appSignal = AppSignal::getInstance();
        $appSignal->setBasePath($fixtureDir);

        $this->assertEquals('unknown', $appSignal->getRevision());
    }

    #[Group('no-extension')]
    public function testInitializeWarnsWhenExtensionIsNotLoaded(): void
    {
        $appSignal = AppSignal::getInstance();

        $warning = $this->callAndCaptureWarnings(fn() => $appSignal->initialize());

        $this->assertStringContainsString('opentelemetry', $warning);
        $this->assertStringContainsString('not loaded', $warning);
    }

    public function testInitializeWarnsWhenConfigIsInvalid(): void
    {
        $invalidConfig = new Config(name: 'Test App');

        $environment = new class ($invalidConfig) implements Environment {
            public function __construct(private Config $config) {}
            public function applyPatches(): void {}
            public function getConfig(): Config
            {
                return $this->config;
            }
        };

        $appSignal = new class ($environment) extends AppSignal {
            public function __construct(private Environment $testEnv) {}

            protected function detectEnvironment(): Environment
            {
                return $this->testEnv;
            }
        };

        AppSignal::setInstance($appSignal);

        $warning = $this->callAndCaptureWarnings(fn() => $appSignal->initialize());

        $this->assertStringContainsString('configuration is invalid', $warning);
        $this->assertStringContainsString('push_api_key', $warning);
        $this->assertStringContainsString('collector_url', $warning);
        $this->assertStringContainsString('environment', $warning);
        $this->assertStringNotContainsString("'name'", $warning);
    }

    /** @var string[] */
    protected array $fixtures = [];

    protected function createTempDir(): string
    {
        $dir = sys_get_temp_dir() . '/appsignal_test_' . uniqid();
        mkdir($dir);
        $this->fixtures[] = $dir;

        return $dir;
    }

    protected function cleanupFixtures(): void
    {
        foreach ($this->fixtures as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($items as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }

            rmdir($dir);
        }

        $this->fixtures = [];
    }
}
