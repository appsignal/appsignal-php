<?php

namespace AppSignal;

use AppSignal\Environments\Environment;
use AppSignal\Environments\Laravel;
use AppSignal\Environments\Symfony;
use AppSignal\Environments\Vanilla;
use AppSignal\Patches\StackTraceFormatterPatch;
use Dotenv\Dotenv;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\SDK\Logs\Processor\SimpleLogRecordProcessor;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;

class AppSignal
{
    use RecordsInstrumentation;
    use RecordsMetrics;

    protected static ?self $instance = null;

    protected bool $initialized = false;
    protected ?string $basePath = null;
    protected ?string $framework = null;
    protected ?Environment $environment = null;

    public static function getInstance(): self
    {
        return static::$instance ??= new self();
    }

    public static function setInstance(?self $instance): void
    {
        static::$instance = $instance;
    }

    public function getBasePath(): ?string
    {
        return $this->basePath;
    }

    public function setBasePath(?string $path): void
    {
        $this->basePath = $path;
    }

    protected function detectEnvironment(): ?Environment
    {
        ["root" => $basePath, "env" => $framework] = $this->findRoot();

        $this->basePath = $basePath;
        $this->framework = $framework;

        if ($framework == 'laravel') {
            return new Laravel($basePath);
        }
        if ($framework == 'symfony') {
            return new Symfony($basePath);
        }
        if ($framework == 'vanilla') {
            return new Vanilla($basePath);
        }
        return null;
    }

    protected function applyGlobalPatches(?Config $config = null): void
    {
        $disabledPatches = $this->getDisabledPatches($config);

        if (!in_array('stack_trace_formatter', $disabledPatches)) {
            (new StackTraceFormatterPatch(appRoot: $this->basePath))();
        }
    }

    /**
     * @return string[]
     */
    protected function getDisabledPatches(?Config $config = null): array
    {
        if ($config?->disablePatches !== null) {
            return $config->disablePatches;
        }

        return array_map(
            fn($value): string => trim($value),
            explode(',', $_ENV['APPSIGNAL_DISABLE_PATCHES'] ?? ''),
        );
    }

    public static function extensionIsLoaded(): bool
    {
        return extension_loaded('opentelemetry');
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        if (!$this->extensionIsLoaded()) {
            trigger_error(
                'AppSignal: the "opentelemetry" PHP extension is not loaded. AppSignal will not be initialized.',
                E_USER_WARNING,
            );

            return;
        }

        $environment = $this->detectEnvironment();

        if (is_null($environment)) {
            return;
        }

        $this->loadEnv();

        $config = $environment->getConfig();

        if (!$config->isValid()) {
            $missing = $config->getMissingFields();

            trigger_error(
                'AppSignal: configuration is invalid. Missing required fields: '
                    . implode(', ', $missing)
                    . '. AppSignal will not be initialized.',
                E_USER_WARNING,
            );

            return;
        }

        $this->applyGlobalPatches($config);

        $environment->applyPatches();

        if (isset($_ENV['_APPSIGNAL_TEST'])) {
            $this->initialized = true;

            return;
        }

        $this->initializeOpenTelemetry($config);
    }


    protected function initializeOpenTelemetry(?Config $config = null): void
    {
        $detectedFramework = $this->framework ?? "PHP";
        $serviceName = ucfirst($detectedFramework) . " Service";

        $resource = ResourceInfoFactory::defaultResource()
            ->merge(
                ResourceInfo::create(
                    Attributes::create([
                        'service.name' => $serviceName,
                        'appsignal.config.name' => $config->name,
                        'appsignal.config.environment' => $config->environment,
                        'appsignal.config.push_api_key' => $config->pushApiKey,
                        'appsignal.config.revision' => $this->getRevision(),
                        'appsignal.config.language_integration' => 'php',
                        'appsignal.config.app_path' => __DIR__,
                        'host.name' => gethostname(),
                    ])
                )
            );

        $spanExporter = new SpanExporter(
            new OtlpHttpTransportFactory()->create("$config->collectorUrl/v1/traces", 'application/x-protobuf')
        );

        $logExporter = new LogsExporter(
            new OtlpHttpTransportFactory()->create("$config->collectorUrl/v1/logs", 'application/x-protobuf')
        );

        $reader = new ExportingReader(
            new MetricExporter(
                new OtlpHttpTransportFactory()->create("$config->collectorUrl/v1/metrics", 'application/x-protobuf')
            )
        );

        $meterProvider = MeterProvider::builder()
            ->setResource($resource)
            ->addReader($reader)
            ->build();


        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(
                BatchSpanProcessor::builder($spanExporter)->build()
            )
            ->setResource($resource)
            ->setSampler(new ParentBased(new AlwaysOnSampler()))
            ->build();

        $loggerProvider = LoggerProvider::builder()
            ->setResource($resource)
            ->addLogRecordProcessor(
                new SimpleLogRecordProcessor($logExporter)
            )
            ->build();

        Sdk::builder()
            ->setTracerProvider($tracerProvider)
            ->setMeterProvider($meterProvider)
            ->setLoggerProvider($loggerProvider)
            ->setPropagator(TraceContextPropagator::getInstance())
            ->setAutoShutdown(true)
            ->buildAndRegisterGlobal();

        $this->initialized = true;
    }

    public function loadEnv(): bool
    {
        if (isset($_ENV['APP_KEY'])) {
            return true;
        }


        if (is_null($this->basePath)) {
            return false;
        }


        $envPath = $this->basePath . '/.env';


        if (file_exists(filename: $envPath)) {
            $dotenv = Dotenv::createImmutable($this->basePath);
            $dotenv->safeLoad();
            return true;
        }

        return false;
    }
    /**
     * @return array<string,string>|array<string,null>|array<null,null>
     */
    protected function findRoot(): array
    {
        foreach (get_included_files() as $file) {
            if (str_ends_with($file, '/vendor/autoload.php')) {
                $rootCandidate = dirname($file, 2);

                if (
                    file_exists($rootCandidate . '/artisan')
                    && file_exists($rootCandidate . '/composer.json')
                    && is_dir($rootCandidate . '/bootstrap')
                ) {
                    return ['root' => $rootCandidate, "env" => "laravel"];
                }

                if (
                    file_exists($rootCandidate . '/symfony.lock')
                    && file_exists($rootCandidate . '/composer.json')
                ) {
                    return ["root" => $rootCandidate, "env" => "symfony"];
                }

                if (
                    file_exists($rootCandidate . '/composer.json')
                ) {
                    return ["root" => $rootCandidate, "env" => "vanilla"];
                }
            }
        }

        return ["root" => null, "env" => null];
    }

    public function getRevision(): string
    {
        $revision = $this->getRevisionFromGit();

        return $revision ? $revision : 'unknown';
    }

    protected function getRevisionFromGit(): ?string
    {
        if (is_null($this->basePath)) {
            return null;
        }

        $command = sprintf(
            'git -C %s rev-parse HEAD 2>/dev/null',
            escapeshellarg($this->basePath),
        );

        return trim(shell_exec($command) ?? "");
    }
}
