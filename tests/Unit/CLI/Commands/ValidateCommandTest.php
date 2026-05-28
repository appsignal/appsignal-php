<?php

namespace Appsignal\Tests\Unit\CLI\Commands;

use Appsignal\CLI\Commands\ValidateCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ValidateCommandTest extends CommandTestCase
{
    public function testReturnsSuccessWhenConfigIsValid(): void
    {
        $projectDir = $this->createProjectDir();
        $this->writeConfigFile($projectDir, [
            'name' => 'Test App',
            'environment' => 'test',
            'push_api_key' => 'test-key',
            'collector_endpoint' => 'https://collector.test',
        ]);

        $tester = new CommandTester(new ValidateCommand($projectDir));
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('The AppSignal config is valid.', $tester->getDisplay());
    }

    public function testReturnsFailureWithMissingFieldsWhenConfigIsEmpty(): void
    {
        $projectDir = $this->createProjectDir();
        $this->writeConfigFile($projectDir, []);

        $tester = new CommandTester(new ValidateCommand($projectDir));
        $tester->execute([]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('The AppSignal config is invalid.', $display);
        $this->assertStringContainsString('push_api_key', $display);
        $this->assertStringContainsString('collector_endpoint', $display);
        $this->assertStringContainsString('name', $display);
        $this->assertStringContainsString('environment', $display);
    }

    public function testReturnsFailureWhenConfigFileDoesNotExist(): void
    {
        $projectDir = $this->createProjectDir();

        $tester = new CommandTester(new ValidateCommand($projectDir));
        $tester->execute([]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('The AppSignal config is invalid.', $tester->getDisplay());
    }
}
