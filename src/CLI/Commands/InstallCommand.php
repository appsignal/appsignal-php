<?php

namespace Appsignal\CLI\Commands;

use Appsignal\Appsignal;
use Composer\InstalledVersions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    public function __construct(
        private readonly string $packageDir,
        private readonly string $appPath,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('install')
            ->setDescription('Install AppSignal, scaffold config file and add auto-instrumentations')
            ->addOption('push_api_key', null, InputOption::VALUE_REQUIRED, 'AppSignal Push API key')
            ->addOption('collector_endpoint', null, InputOption::VALUE_REQUIRED, 'AppSignal collector endpoint')
            ->addOption('app_name', null, InputOption::VALUE_REQUIRED, 'Your application name')
            ->addOption('app_environment', null, InputOption::VALUE_REQUIRED, 'Your application environment')
            ->addOption('skip-demo', null, InputOption::VALUE_NONE, 'Skip running the demo after install');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = InstalledVersions::getPrettyVersion("appsignal/appsignal-php");

        $output->writeln("    _             ___ _                _ ");
        $output->writeln("   /_\  _ __ _ __/ __(_)__ _ _ _  __ _| |");
        $output->writeln("  / _ \| '_ \ '_ \__ \ / _` | ' \/ _` | |");
        $output->writeln(" /_/ \_\ .__/ .__/___/_\__, |_||_\__,_|_|");
        $output->writeln("       |_|  |_|        |___/             ");


        $output->writeln("");
        $output->writeln(" <fg=default;bg=yellow> Info </> Running <info>AppSignal for PHP {$version}</info> installer");
        $output->writeln("");

        $appsignal = Appsignal::getInstance();
        $appsignal->setBasePath($this->appPath);
        $config = $appsignal->loadConfig();
        $framework = $appsignal->getFramework();

        $output->writeln("<fg=gray>Environment</>");

        $output->writeln(" ✔ Detected <fg=green>" . $this->getFrameworkDisplayName($framework) . "</> application");

        if ($framework != 'vanilla') {
            // autoinstrumentation installation
            $autoInstrumentation = "open-telemetry/opentelemetry-auto-$framework";

            $command = new Process(
                command: [
                    'composer',
                    'show',
                    $autoInstrumentation,
                ],
                cwd: $this->appPath,
            );
            $exitCode = $command->run();


            if ($exitCode === 0) {
                $output->writeln(" - Found auto-instrumentation package, skipping installation");
            } else {
                $progress = new ProgressIndicator($output);
                $progress->start("Installing <fg=yellow>\"$autoInstrumentation\"</>...");
                $command = new Process([
                    'composer',
                    'require',
                    $autoInstrumentation,
                    '--no-interaction',
                    '--no-ansi',
                    '--quiet',
                ], cwd: $this->appPath);
                $command->run(function () use ($progress) {
                    $progress->advance();
                });

                $progress->finish("Installed auto-instrumentation package <fg=gray>$autoInstrumentation</>");
            }
        }

        // config file setup
        $configTemplate = $this->getConfigTemplateForFramework($framework);
        $configTargetDir = $this->appPath . '/config';
        $configTarget = $configTargetDir . '/appsignal.php';

        if (!file_exists($configTarget)) {
            if (!is_dir($configTargetDir)) {
                mkdir($configTargetDir, 0o755, true);
            }
            copy($configTemplate, $configTarget);
            $output->writeln(" ✔ Created AppSignal config file <fg=gray>$configTarget</>");
            // reload config
            $config = $appsignal->loadConfig(forceReload: true);
        } else {
            $output->writeln(" - Found AppSignal config file");
        }

        $output->writeln("");
        $output->writeln("<fg=gray>Configure AppSignal</>");

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $pushApiKey = $input->getOption('push_api_key');
        $collectorEndpoint = $input->getOption('collector_endpoint');
        $appName = $input->getOption('app_name');
        $appEnvironment = $input->getOption('app_environment');

        if ($pushApiKey && $collectorEndpoint && $appName && $appEnvironment) {
            $output->writeln(" - Command arguments provided, skipping configuration");
        }

        if (!$pushApiKey) {
            $pushApiKey = $helper->ask(
                $input,
                $output,
                new Question(
                    question: ' Enter your Push API key' . ($config->pushApiKey ? " <fg=gray>[$config->pushApiKey]</>: " : ": "),
                    default: $config->pushApiKey,
                ),
            );
        }

        if (!$collectorEndpoint) {
            $collectorEndpoint = $helper->ask(
                $input,
                $output,
                new Question(
                    question: ' Collector endpoint' . ($config->collectorEndpoint ? " <fg=gray>[$config->collectorEndpoint]</>: " : ": "),
                    default: $config->collectorEndpoint
                )
            );
        }

        if (!$appName) {
            $appName = $helper->ask(
                $input,
                $output,
                new Question(
                    question: ' App name' . ($config->name ? " <fg=gray>[$config->name]</>: " : ": "),
                    default: $config->name,
                ),
            );
        }

        if (!$appEnvironment) {
            $appEnvironment = $helper->ask(
                $input,
                $output,
                new Question(
                    question: ' App environment' . ($config->environment ? " <fg=gray>[$config->environment]</>: " : ": "),
                    default: $config->environment,
                ),
            );
        }

        $newConfig = $config->withOverrides([
            'active' => true,
            'name' => $appName,
            'collectorEndpoint' => $collectorEndpoint,
            'pushApiKey' => $pushApiKey,
            'environment' => $appEnvironment,
        ]);
        $appsignal->setConfig($newConfig);

        $shouldUpdateEnv = false;
        if (!$input->getOption('skip-demo')) {
            $output->writeln("");
            $demoCommand = $this->getApplication()?->find('demo');
            if ($demoCommand) {
                $exitCode = $demoCommand->run(new ArrayInput([]), $output);
                $shouldUpdateEnv = $exitCode == 0;
            }
        }

        if ($shouldUpdateEnv) {
            $envFile = $this->appPath . '/.env';
            touch($envFile);
            $envContents = file_get_contents($envFile);
            $newContent = "";

            if (!preg_match('/^APPSIGNAL_PUSH_API_KEY/m', $envContents)) {
                $newContent .= PHP_EOL . "APPSIGNAL_PUSH_API_KEY=$pushApiKey" . PHP_EOL;
            }

            if (!preg_match('/^APPSIGNAL_COLLECTOR_ENDPOINT/m', $envContents)) {
                $newContent .= "APPSIGNAL_COLLECTOR_ENDPOINT=$collectorEndpoint" . PHP_EOL;
            }

            if (!preg_match('/^APPSIGNAL_APP_NAME/m', $envContents)) {
                $newContent .= "APPSIGNAL_APP_NAME=$appName" . PHP_EOL;
            }

            if (!preg_match('/^APPSIGNAL_APP_ENV/m', $envContents)) {
                $newContent .= "APPSIGNAL_APP_ENV=$appEnvironment" . PHP_EOL;
            }
            file_put_contents($envFile, $newContent, FILE_APPEND);
            $output->writeln(" ✔ updated AppSignal environment variables <fg=gray>in $envFile</>");
        }

        return Command::SUCCESS;
    }

    protected function getConfigTemplateForFramework(string $framework): string
    {
        return match ($framework) {
            'laravel' => $this->packageDir . '/config-stubs/appsignal.laravel.php',
            default => $this->packageDir . '/config-stubs/appsignal.php',
        };
    }

    protected function getFrameworkDisplayName(string $framework): string
    {
        return match ($framework) {
            'vanilla' => 'PHP',
            default => ucfirst($framework),
        };
    }
}
