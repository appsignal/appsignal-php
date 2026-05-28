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
    public function getHelp(): string
    {
        return '';
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
