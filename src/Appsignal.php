<?php

namespace Appsignal;

use Appsignal\Environments\Environment;
use Appsignal\Environments\Laravel;
use Appsignal\Environments\Symfony;
use Appsignal\Environments\Vanilla;
use Appsignal\Patches\AlignedStackTraceFormatterPatch;
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

class Appsignal
{
    use RecordsInstrumentation;
    use RecordsLogs;
    use RecordsMetrics;

    protected static ?self $instance = null;
    protected bool $initialized = false;
    protected ?string $basePath = null;
    protected ?string $framework = null;
    protected ?Environment $environment = null;
    protected ?Config $config = null;

    public static function getInstance(): self
    {
        return static::$instance ??= new self();
    }

    public static function setInstance(?self $instance): void
    {
        static::$instance = $instance;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function getBasePath(): ?string
    {
        return $this->basePath;
    }

    public function setBasePath(?string $path): void
    {
        $this->basePath = $path;
    }

    public function setFramework(?string $framework): void
    {
        $this->framework = $framework;
    }

    public function getFramework(): string
    {
        return $this->framework;
    }

    public function loadConfig(bool $forceReload = false): ?Config
    {
        if (!$forceReload && $this->config !== null) {
            return $this->config;
        }

        $environment = $this->detectEnvironment();

        if ($environment === null) {
            return null;
        }

        $this->environment = $environment;
        $this->config = $environment->getConfig();
        return $this->config;
    }

    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    protected function detectEnvironment(): ?Environment
    {
        if (!$this->basePath || !$this->framework) {
            ["root" => $basePath, "env" => $framework] = $this->findRoot();

            $this->setBasePath($basePath);
            $this->setFramework($framework);
        }

        if ($this->framework == 'laravel') {
            return new Laravel($this->basePath);
        }
        if ($this->framework == 'symfony') {
            return new Symfony($this->basePath);
        }
        if ($this->framework == 'vanilla') {
            return new Vanilla($this->basePath);
        }
        return null;
    }

    protected function applyGlobalPatches(?Config $config = null): void
    {
        $disabledPatches = $this->getDisabledPatches($config);

        if (!in_array('stack_trace_formatter', $disabledPatches)) {
            (new AlignedStackTraceFormatterPatch(appRoot: $config->appPath ?? $this->basePath))();
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
                'Appsignal: the "opentelemetry" PHP extension is not loaded. Appsignal will not be initialized.',
                E_USER_WARNING,
            );

            return;
        }

        $environment = $this->environment ?? $this->detectEnvironment();

        if (is_null($environment)) {
            trigger_error(
                'Appsignal: could not detect application environment. Appsignal will not be initialized.',
                E_USER_WARNING,
            );

            return;
        }

        $this->environment = $environment;
        $config = $this->config ?? $environment->getConfig();

        if (!$config->isValid()) {
            $missing = $config->getMissingFields();

            trigger_error(
                'Appsignal: configuration is invalid. Missing required fields: '
                    . implode(', ', $missing)
                    . '.',
                E_USER_WARNING,
            );

            return;
        }

        if (!$config->active) {
            return;
        }

        $this->applyGlobalPatches($config);

        $environment->applyPatches();

        $this->initializeOpenTelemetry($config);
        $this->initialized = true;
    }


    public function buildResource(Config $config): ResourceInfo
    {
        return ResourceInfoFactory::defaultResource()
            ->merge(
                ResourceInfo::create(
                    Attributes::create([
                        'service.name' => ucfirst($this->framework ?? 'PHP') . ' Service',
                        'host.name' => gethostname() ?: 'unknown',
                        'appsignal.config.revision' => $this->getRevision(),
                        'appsignal.config.language_integration' => 'php',
                        'appsignal.config.app_path' => $this->getBasePath(),
                        ...new OpenTelemetryResourceAttributes($config)->toArray(),
                    ])
                )
            );
    }

    protected function initializeOpenTelemetry(?Config $config = null): void
    {
        if (isset($_ENV['_APPSIGNAL_TEST'])) {
            return;
        }

        $resource = $this->buildResource($config);

        $otlpProtocol = $_ENV['_APPSIGNAL_OTLP_PROTOCOL'] ?? 'application/x-protobuf';

        $spanExporter = new SpanExporter(
            new OtlpHttpTransportFactory()->create("$config->collectorEndpoint/v1/traces", $otlpProtocol)
        );

        $logExporter = new LogsExporter(
            new OtlpHttpTransportFactory()->create("$config->collectorEndpoint/v1/logs", $otlpProtocol)
        );

        $reader = new ExportingReader(
            new MetricExporter(
                new OtlpHttpTransportFactory()->create("$config->collectorEndpoint/v1/metrics", $otlpProtocol)
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
