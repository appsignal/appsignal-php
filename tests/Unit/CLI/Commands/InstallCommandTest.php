<?php

namespace Appsignal\Tests\Unit\CLI\Commands;

use Appsignal\Appsignal;
use Appsignal\CLI\Application;
use Appsignal\CLI\Commands\DemoCommand;
use Appsignal\CLI\Commands\InstallCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class InstallCommandTest extends CommandTestCase
{
    protected function tearDown(): void
    {
        Appsignal::setInstance(null);
        parent::tearDown();
    }

    public function testCreatesConfigFileWhenNotPresent(): void
    {
        $projectDir = $this->createProjectDir();

        $tester = new CommandTester($this->makeApplication($projectDir)->find('install'));
        $tester->execute([
            '--push_api_key' => 'test-key',
            '--collector_endpoint' => 'https://collector.test',
            '--app_name' => 'Test App',
            '--app_environment' => 'production',
            '--skip-demo' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertFileExists($projectDir . '/config/appsignal.php');
        $this->assertStringContainsString('Created AppSignal config file', $tester->getDisplay());
    }

    public function testSkipsConfigCreationWhenConfigExists(): void
    {
        $projectDir = $this->createProjectDir();
        $this->writeConfigFile($projectDir, []);

        $tester = new CommandTester($this->makeApplication($projectDir)->find('install'));
        $tester->execute([
            '--push_api_key' => 'test-key',
            '--collector_endpoint' => 'https://collector.test',
            '--app_name' => 'Test App',
            '--app_environment' => 'production',
            '--skip-demo' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Found AppSignal config file', $tester->getDisplay());
        $this->assertStringNotContainsString('Created AppSignal config file', $tester->getDisplay());
    }

    public function testWritesEnvFileWithKeysAfterDemo(): void
    {
        $projectDir = $this->createProjectDir();

        $tester = new CommandTester($this->makeApplication($projectDir, $this->makeDemoStub())->find('install'));
        $tester->execute([
            '--push_api_key' => 'test-key',
            '--collector_endpoint' => 'https://collector.test',
            '--app_name' => 'Test App',
            '--app_environment' => 'production',
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $envContents = (string) file_get_contents($projectDir . '/.env');
        $this->assertStringContainsString('APPSIGNAL_PUSH_API_KEY=test-key', $envContents);
        $this->assertStringContainsString('APPSIGNAL_COLLECTOR_ENDPOINT=https://collector.test', $envContents);
        $this->assertStringContainsString('APPSIGNAL_APP_NAME=Test App', $envContents);
        $this->assertStringContainsString('APPSIGNAL_APP_ENV=production', $envContents);
    }

    public function testSkipsUpdatingEnvFileWhenDemoFails(): void
    {
        $projectDir = $this->createProjectDir();

        $tester = new CommandTester($this->makeApplication($projectDir, $this->makeDemoStub(Command::FAILURE))->find('install'));
        $tester->execute([
            '--push_api_key' => 'test-key',
            '--collector_endpoint' => 'https://collector.test',
            '--app_name' => 'Test App',
            '--app_environment' => 'production',
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $envContents = (string) file_get_contents($projectDir . '/.env');
        $this->assertStringNotContainsString('APPSIGNAL_PUSH_API_KEY', $envContents);
        $this->assertStringNotContainsString('APPSIGNAL_COLLECTOR_ENDPOINT', $envContents);
        $this->assertStringNotContainsString('APPSIGNAL_APP_NAME', $envContents);
        $this->assertStringNotContainsString('APPSIGNAL_APP_ENV', $envContents);
    }


    protected function makeApplication(string $projectDir, ?Command $demoCommand = null): Application
    {
        $app = new Application('appsignal', '1.0');
        $app->addCommand(new InstallCommand(self::$packageDir, $projectDir));
        $app->addCommand($demoCommand ?? new DemoCommand($projectDir));
        return $app;
    }

    protected function makeDemoStub(int $exitCode = Command::SUCCESS): Command
    {
        return new class ($exitCode) extends Command {
            public function __construct(private readonly int $exitCode)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->setName('demo');
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                return $this->exitCode;
            }
        };
    }
}
