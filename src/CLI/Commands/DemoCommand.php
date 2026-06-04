<?php

namespace Appsignal\CLI\Commands;

use Appsignal\Appsignal;
use Appsignal\CLI\LogCatcher;
use Appsignal\CLI\Application;
use Appsignal\Severity;
use OpenTelemetry\API\LoggerHolder;
use OpenTelemetry\API\Trace\SpanKind;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DemoCommand extends Command
{
    public const CONFIG_SHOW_COLLECTOR_INFO = 'demo.show_collector_info';

    public function __construct(
        private readonly string $appPath,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('demo')
            ->setDescription('Send a demo trace, exception, and log to AppSignal');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $_ENV['APPSIGNAL_ACTIVE'] = 'true';
        $io = new SymfonyStyle($input, $output);

        /** @var Application $cli */
        $cli = $this->getApplication();
        $showCollectorInfo = $cli->getInternalConfig(self::CONFIG_SHOW_COLLECTOR_INFO, false);

        $appsignal = Appsignal::getInstance();
        $appsignal->setBasePath($this->appPath);
        $appsignal->initialize();

        $config = $appsignal->loadConfig();

        LoggerHolder::set($logCatcher = new LogCatcher());

        $output->writeln('<fg=gray>AppSignal demo</>');

        if ($showCollectorInfo && $config->usingHostedCollector()) {
            $output->writeln(" ! Using a <fg=white;options=bold>Hosted collector</></>.");
            $io->block("This option is ideal for most users — it requires no setup or maintenance, and incurs no infrastructure costs.\nIf you have advanced needs, you can opt for a Self-hosted collector, which offers enhanced privacy, greater compliance flexibility, and access to additional metrics.", prefix: "   ", style: 'fg=yellow');
            $io->block("To learn more, visit https://docs.appsignal.com/collector/hosted-vs-self-hosted.html", prefix: "   ", style: "fg=yellow");
        }

        if (!$appsignal->isInitialized()) {
            $output->writeln(' <fg=red>Error:</> Could not initialize AppSignal. Double-check your configuration.');
            return Command::FAILURE;
        }

        $logProgress = new ProgressIndicator($output);
        $logProgress->start('Sending a log ...');
        Appsignal::log(
            message: 'AppSignal test log.',
            severity: Severity::INFO,
            attributes: ['type' => 'appsignal_demo'],
        );

        if ($logCatcher->hasErrors()) {
            $output->writeln(' <fg=red>Error:</> Failed to reach AppSignal. Check push_api_key and collector_endpoint configuration.');

            return Command::FAILURE;
        }
        $logProgress->finish('Sent a log');


        $traceProgress = new ProgressIndicator($output);
        $traceProgress->start('Sending an example trace ...');
        Appsignal::instrument(name: 'GET /demo', closure: function () {
            Appsignal::addTags(['demo-trace' => true]);
            Appsignal::setAction('DemoController::show');

            Appsignal::instrument(name: 'normal_span', closure: fn() => usleep(100000));

            Appsignal::instrument(
                name: 'slow_span',
                closure: function () {
                    sleep(2);
                    Appsignal::log(
                        message: 'This is an AppSignal demo log message.',
                        severity: \Appsignal\Severity::INFO,
                        attributes: ['type' => 'appsignal_demo'],
                    );
                },
            );
        });

        // @phpstan-ignore if.alwaysFalse
        if ($logCatcher->hasErrors()) {
            $output->writeln(' <fg=red>Error:</> Failed to reach AppSignal. Check push_api_key and collector_endpoint configuration.');
            return Command::FAILURE;
        }
        $traceProgress->finish('Sent a trace');

        $errorProgress = new ProgressIndicator($output);
        $errorProgress->start('Sending an exception ...');
        Appsignal::instrument(name: 'GET /demo-with-error', attributes: ['http.response.status_code' => 500], spanKind: SpanKind::KIND_SERVER, closure: function () {
            Appsignal::addTags(['demo-trace' => true]);
            Appsignal::setAction('DemoController::showWithError');
            Appsignal::setError(new DemoException('TestException: AppSignal demo exception'));
        });

        // @phpstan-ignore if.alwaysFalse
        if ($logCatcher->hasErrors()) {
            $output->writeln(' <fg=red>Error:</> Failed to reach AppSignal. Check push_api_key and collector_endpoint configuration.');
            return Command::FAILURE;
        }
        $errorProgress->finish('Sent an exception');

        $output->writeln(' ✔ <fg=green>Finished sending data to AppSignal</>');

        $io->block("It may take around a minute for the data to appear on https://appsignal.com/accounts", prefix: "   ");

        return Command::SUCCESS;
    }
}

class DemoException extends \Exception {}
