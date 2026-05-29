<?php

namespace Appsignal\Tests\Unit\CLI\Commands;

use Appsignal\Appsignal;
use Appsignal\CLI\Application;
use Appsignal\CLI\Commands\DemoCommand;
use Appsignal\Config;
use Appsignal\Tests\Support\CapturesOTLPRequests;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DemoCommandTest extends CommandTestCase
{
    use CapturesOTLPRequests;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::startOtlpServer();
    }

    public static function tearDownAfterClass(): void
    {
        self::stopOtlpServer();
        parent::tearDownAfterClass();
    }

    #[RunInSeparateProcess]
    public function testReturnsFailureWhenNotInitialized(): void
    {
        $projectDir = $this->createProjectDir();

        // Suppress warning which we trigger in Appsignal::initialize()
        set_error_handler(fn() => true, E_USER_WARNING);
        $cli = new Application();
        $cli->addCommand(new DemoCommand($projectDir));
        $tester = new CommandTester($cli->find('demo'));
        $tester->execute([]);
        restore_error_handler();

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Could not initialize AppSignal', $tester->getDisplay());
    }

    // This is a slow test, since the demo command sleeps for 3 seconds.
    #[RunInSeparateProcess]
    public function testReturnsSuccessAndOutputsFinishedMessageWhenOtlpServerResponds(): void
    {
        $projectDir = $this->createProjectDir();

        $_ENV['_APPSIGNAL_OTLP_PROTOCOL'] = 'application/json';

        Appsignal::getInstance()->setConfig(new Config(
            active: true,
            name: 'Test App',
            environment: 'test',
            pushApiKey: 'test-key',
            collectorEndpoint: 'http://127.0.0.1:' . self::$otlpPort,
        ));

        $cli = new Application();
        $cli->addCommand(new DemoCommand($projectDir));
        $tester = new CommandTester($cli->find('demo'));

        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Finished sending data to AppSignal', $tester->getDisplay());
    }
}
