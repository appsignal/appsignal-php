<?php

namespace Appsignal\CLI;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends BaseApplication
{
    /** @var array<string, mixed> */
    protected array $internalConfig = [];

    public function getHelp(): string
    {
        return '';
    }

    public function getInternalConfig(string $key, mixed $default = null): mixed
    {
        return $this->internalConfig[$key] ?? $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function setInternalConfig(array $config): void
    {
        $this->internalConfig = array_merge($this->internalConfig, $config);
    }

    public function clearInternalConfig(string ...$keys): void
    {
        if (empty($keys)) {
            $this->internalConfig = [];
        } else {
            foreach ($keys as $key) {
                unset($this->internalConfig[$key]);
            }
        }
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::REQUIRED),
        ]);
    }

    protected function getDefaultCommands(): array
    {
        $commands = array_map(
            fn(Command $cmd) => $cmd->setHidden(true),
            parent::getDefaultCommands()
        );

        $commands[] = new class extends Command {
            protected function configure(): void
            {
                $this->setName('list')->setHidden(true);
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $output->writeln('Available commands:');
                foreach ($this->getApplication()->all() as $command) {
                    if (!$command->isHidden()) {
                        $output->writeln(sprintf('  <info>%-12s</info> %s', $command->getName(), $command->getDescription()));
                    }
                }
                return Command::SUCCESS;
            }
        };

        return $commands;
    }
}
