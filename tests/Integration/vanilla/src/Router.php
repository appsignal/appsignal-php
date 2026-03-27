<?php

namespace App;

use Appsignal\Appsignal;
use Appsignal\Integrations\Monolog\Handler;
use Monolog\Logger;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

/**
 * Basic router that creates Laravel and Symfony-like root spans
 */
class Router
{
    public static function handle(string $method, string $uri): void
    {
        $tracer = Globals::tracerProvider()->getTracer('app');
        $span = $tracer->spanBuilder("$method $uri")
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->setAttribute('http.request.method', $method)
            ->setAttribute('http.route', $uri)
            ->startSpan();
        $scope = $span->activate();

        try {
            static::route($method, $uri);
            $span->setAttribute('http.response.status_code', 200);
        } catch (\Throwable $e) {
            Appsignal::setError($e);
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            $span->setAttribute('http.response.status_code', 500);
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    protected static function route(string $method, string $uri): void
    {
        $logger = new Logger('app');
        $logger->pushHandler(Handler::withLevel('info'));

        match ($uri) {
            '/' => null,
            '/instrument' => Appsignal::instrument(
                'my-span',
                [
                    'string-attribute' => 'abcdef',
                    'int-attribute' => 1234,
                    'bool-attribute' => true,
                ],
                function () {}
            ),
            '/instrument-nested' => Appsignal::instrument(
                'parent',
                ['msg' => 'from parent span'],
                function () {
                    $span = Appsignal::instrument('child', ['msg' => 'from child span']);
                    $span->end();
                }
            ),
            '/set-action' => Appsignal::setAction('my action'),
            '/custom-data' => Appsignal::addAttributes([
                'string-attribute' => 'abcdef',
                'int-attribute' => 1234,
                'bool-attribute' => true,
            ]),
            '/tags' => Appsignal::addTags([
                'string-tag' => 'some value',
                'integer-tag' => 1234,
                'bool-tag' => true,
            ]),
            '/log' => $logger->info('My log'),
            '/log-with-attributes' => $logger->info('My log with attributes', ['foo' => 'bar']),
            '/set-gauge' => (function () {
                Appsignal::setGauge('my_gauge', 12);
                Appsignal::setGauge('my_gauge_with_attributes', 13, ['region' => 'eu']);
            })(),
            '/add-distribution-values' => (function () {
                Appsignal::addDistributionValue('memory_usage', 50);
                Appsignal::addDistributionValue('memory_usage', 70);
                Appsignal::addDistributionValue('with_attributes', 10, ['region' => 'eu']);
                Appsignal::addDistributionValue('with_attributes', 20, ['region' => 'eu']);
                Appsignal::addDistributionValue('with_attributes', 30, ['region' => 'eu']);
            })(),
            '/counter' => (function () {
                Appsignal::incrementCounter('my_counter', 1);
                Appsignal::incrementCounter('my_counter', 3, ['region' => 'eu']);
            })(),
            '/error' => (function () {
                $controller = new ErrorsController();
                $controller->show();
            })(),
            '/error-nested' => (function () {
                $controller = new ErrorsController();
                $controller->nested();
            })(),
            default => throw new \RuntimeException("Not found: $uri"),
        };
    }
}
