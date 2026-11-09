<?php

namespace Appsignal\Environments;

use Appsignal\Config;
use Appsignal\OpenTelemetry\GlobalsLoggerProvider;
use Appsignal\OpenTelemetry\GlobalsTracerProvider;
use OpenTelemetry\API\Configuration\Noop\NoopConfigProperties;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\Context as InstrumentationContext;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\ExtensionHookManager;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\Instrumentation;
use OpenTelemetry\SDK\Propagation\LateBindingTextMapPropagator;
use OpenTelemetry\SDK\SdkAutoloader;

class Laravel implements Environment
{
    use HasPatches;

    private const string LARAVEL_INSTRUMENTATION = 'OpenTelemetry\Contrib\Instrumentation\Laravel\LaravelInstrumentation';

    /** @var array<int, class-string|object> */
    protected array $patches = [];

    public function __construct(protected ?string $basePath = null) {}

    public function registerInstrumentation(): void
    {
        if (SdkAutoloader::isEnabled()) {
            return;
        }

        $instrumentationClass = self::LARAVEL_INSTRUMENTATION;

        if (!is_a($instrumentationClass, Instrumentation::class, true)) {
            return;
        }

        $context = new InstrumentationContext(
            tracerProvider: new GlobalsTracerProvider(),
            loggerProvider: new GlobalsLoggerProvider(),
            propagator: new LateBindingTextMapPropagator(fn() => Globals::propagator()),
        );

        // @phpstan-ignore class.notFound
        new $instrumentationClass()->register(
            new ExtensionHookManager(),
            new NoopConfigProperties(),
            $context,
        );
    }

    public function getConfig(): Config
    {
        if (class_exists(\Dotenv\Dotenv::class)) {
            \Dotenv\Dotenv::createImmutable($this->basePath)->safeLoad();
        }
        return Config::load($this->basePath . Config::CONFIG_PATH);
    }
}
