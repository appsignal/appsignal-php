<?php

namespace Appsignal\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateCommand extends Command
{
    public function __construct(
        private readonly string $projectRoot,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('validate')
            ->setDescription('Check if the AppSignal config is valid');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $environment = match (true) {
            class_exists(\Illuminate\Foundation\Application::class) => new \Appsignal\Environments\Laravel($this->projectRoot),
            class_exists(\Symfony\Component\HttpKernel\Kernel::class) => new \Appsignal\Environments\Symfony($this->projectRoot),
            default => new \Appsignal\Environments\Vanilla($this->projectRoot),
        };

        $config = $environment->getConfig();

        if (!$config->isValid()) {
            $output->writeln('The AppSignal config is invalid. Missing: ' . implode(', ', $config->getMissingFields()));
            return Command::FAILURE;
        }

        $output->writeln('The AppSignal config is valid.');
        return Command::SUCCESS;
    }
}
