<?php

declare(strict_types=1);

namespace AppSignal\Patches\Symfony;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\Context\Context;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Router;

use function OpenTelemetry\Instrumentation\hook;

/**
 * Patches the opentelemetry-auto-symfony instrumentation:
 *
 * 1. Replaces route names with route template paths for http.route.
 *
 * 2. Fixes missing traces for requests that trigger sub-requests (e.g.
 *    render_esi(), render(controller(...))).
 *
 *    The auto-symfony instrumentation creates a span and pushes a context
 *    scope in the HttpKernel::handle pre-hook for every request, including
 *    sub-requests. However, its handle post-hook only detaches the scope
 *    when there is an exception — on the happy path it's a no-op. This
 *    leaves sub-request scopes on the context stack, so when the terminate
 *    post-hook runs it pops the wrong scope (a sub-request's) instead of
 *    the main request's, and the main request span is never ended/exported.
 *
 *    We fix this by adding our own handle post-hook that detaches and ends
 *    the span for sub-requests on the happy path, keeping the context stack
 *    clean for the terminate hook.
 */
final class HttpRoutePatch
{
    private const ROUTE_TEMPLATE_ATTR = '_appsignal_route_template';

    public function __invoke(): void
    {
        if (!class_exists(HttpKernel::class) || !class_exists(Router::class)) {
            return;
        }

        $this->registerRouteTemplateHooks();
        $this->registerSubRequestScopeFixHook();
    }

    /**
     * Resolves route template paths and swaps them in before the
     * auto-instrumentation reads _route in the terminate hook.
     */
    private function registerRouteTemplateHooks(): void
    {
        hook(
            Router::class,
            'matchRequest',
            post: static function (Router $router, array $params, array $returnValue): void {
                $routeName = $returnValue['_route'] ?? null;

                if (!$routeName) {
                    return;
                }

                try {
                    $route = $router->getRouteCollection()->get($routeName);

                    if ($route) {
                        $request = $params[0];
                        $request->attributes->set(self::ROUTE_TEMPLATE_ATTR, $route->getPath());
                    }
                } catch (\Throwable $e) {
                    // Don't break the request for telemetry
                }
            },
        );

        hook(
            HttpKernel::class,
            'terminate',
            pre: static function (HttpKernel $kernel, array $params): void {
                $request = $params[0];
                $routeTemplate = $request->attributes->get(self::ROUTE_TEMPLATE_ATTR);

                if ($routeTemplate) {
                    $request->attributes->set('_route', $routeTemplate);
                    $request->attributes->remove(self::ROUTE_TEMPLATE_ATTR);
                }
            },
        );
    }

    /**
     * Detaches and ends spans for sub-requests on the happy path.
     *
     * The auto-symfony handle post-hook is a no-op when there is no
     * exception, so sub-request scopes pile up on the context stack.
     * This hook runs after the auto-symfony one (registered later) and
     * cleans up sub-request scopes so the main request scope is on top
     * when terminate fires.
     */
    private function registerSubRequestScopeFixHook(): void
    {
        hook(
            HttpKernel::class,
            'handle',
            post: static function (
                HttpKernel $kernel,
                array $params,
                ?Response $response,
                ?\Throwable $exception,
            ): void {
                $type = $params[1] ?? HttpKernelInterface::MAIN_REQUEST;

                if ($type !== HttpKernelInterface::SUB_REQUEST) {
                    return;
                }

                // When there is an exception, the auto-symfony post-hook
                // already detaches the scope (but doesn't end the span).
                // We skip here to avoid detaching the wrong scope.
                if ($exception !== null) {
                    return;
                }

                $scope = Context::storage()->scope();
                if ($scope === null) {
                    return;
                }

                $scope->detach();

                $span = Span::fromContext($scope->context());
                if ($response !== null) {
                    $span->setAttribute('http.response.status_code', $response->getStatusCode());
                }
                $span->end();
            },
        );
    }
}
