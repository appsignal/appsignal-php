<?php

namespace Appsignal\Tests\Unit;

use Appsignal\Config;
use Appsignal\OpenTelemetryResourceAttributes;
use PHPUnit\Framework\TestCase;

class OpenTelemetryResourceConfigTest extends TestCase
{
    public function testFilterAttributes(): void
    {
        $config = new Config(filterAttributes: ['secret', 'key']);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame(
            ['secret', 'key'],
            $attributes['appsignal.config.filter_attributes'],
        );
    }

    public function testFilterFunctionParameters(): void
    {
        $config = new Config(filterFunctionParameters: ['myFunc', 'secretFunc']);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame(
            ['myFunc', 'secretFunc'],
            $attributes['appsignal.config.filter_function_parameters'],
        );
    }

    public function testFilterRequestQueryParameters(): void
    {
        $config = new Config(filterRequestQueryParameters: ['password', 'token']);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame(
            ['password', 'token'],
            $attributes['appsignal.config.filter_request_query_parameters'],
        );
    }

    public function testFilterRequestPayload(): void
    {
        $config = new Config(filterRequestPayload: ['credit_card', 'ssn']);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame(
            ['credit_card', 'ssn'],
            $attributes['appsignal.config.filter_request_payload'],
        );
    }

    public function testFilterRequestSessionData(): void
    {
        $config = new Config(filterRequestSessionData: ['session_token', 'user_id']);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame(
            ['session_token', 'user_id'],
            $attributes['appsignal.config.filter_request_session_data'],
        );
    }

    public function testIgnoreActions(): void
    {
        $config = new Config(ignoreActions: ['GET /health', 'GET /ping']);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame(
            ['GET /health', 'GET /ping'],
            $attributes['appsignal.config.ignore_actions'],
        );
    }

    public function testIgnoreErrors(): void
    {
        $config = new Config(ignoreErrors: ['RuntimeException']);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame(
            ['RuntimeException'],
            $attributes['appsignal.config.ignore_errors'],
        );
    }

    public function testIgnoreLogs(): void
    {
        $config = new Config(ignoreLogs: ['^done$', 'Task .* completed successfully']);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame(
            ['^done$', 'Task .* completed successfully'],
            $attributes['appsignal.config.ignore_logs'],
        );
    }

    public function testIgnoreNamespaces(): void
    {
        $config = new Config(ignoreNamespaces: ['health', 'monitoring']);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame(
            ['health', 'monitoring'],
            $attributes['appsignal.config.ignore_namespaces'],
        );
    }

    public function testRequestHeaders(): void
    {
        $config = new Config(requestHeaders: ['x-request-id', 'x-forwarded-for']);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame(
            ['x-request-id', 'x-forwarded-for'],
            $attributes['appsignal.config.request_headers'],
        );
    }

    public function testEmptyRequestHeadersSet(): void
    {
        $config = new Config(requestHeaders: []);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame(
            [],
            $attributes['appsignal.config.request_headers'],
        );
    }

    public function testNullRequestHeadersNotSet(): void
    {
        $config = new Config(requestHeaders: null);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertFalse(array_key_exists('appsignal.config.request_headers', $attributes));
    }

    public function testResponseHeaders(): void
    {
        $config = new Config(responseHeaders: ['x-powered-by']);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame(
            ['x-powered-by'],
            $attributes['appsignal.config.response_headers'],
        );
    }

    public function testEmptyResponseHeadersSet(): void
    {
        $config = new Config(responseHeaders: []);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame(
            [],
            $attributes['appsignal.config.response_headers'],
        );
    }

    public function testNullResponseHeadersNotSet(): void
    {
        $config = new Config(responseHeaders: null);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertArrayNotHasKey(
            'appsignal.config.response_headers',
            $attributes,
        );
    }

    public function testSendFunctionParametersFalse(): void
    {
        $config = new Config(sendFunctionParameters: false);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertFalse(
            $attributes['appsignal.config.send_function_parameters'],
        );
    }

    public function testSendFunctionParametersTrueNotSet(): void
    {
        $config = new Config(sendFunctionParameters: true);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertArrayNotHasKey(
            'appsignal.config.send_function_parameters',
            $attributes,
        );
    }

    public function testSendRequestQueryParametersFalse(): void
    {
        $config = new Config(sendRequestQueryParameters: false);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertFalse(
            $attributes['appsignal.config.send_request_query_parameters'],
        );
    }

    public function testSendRequestQueryParametersTrueNotSet(): void
    {
        $config = new Config(sendRequestQueryParameters: true);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertArrayNotHasKey(
            'appsignal.config.send_request_query_parameters',
            $attributes,
        );
    }

    public function testSendRequestPayloadFalse(): void
    {
        $config = new Config(sendRequestPayload: false);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertFalse(
            $attributes['appsignal.config.send_request_payload'],
        );
    }

    public function testSendRequestPayloadTrueNotSet(): void
    {
        $config = new Config(sendRequestPayload: true);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertArrayNotHasKey(
            'appsignal.config.send_request_payload',
            $attributes,
        );
    }

    public function testSendRequestSessionDataFalse(): void
    {
        $config = new Config(sendRequestSessionData: false);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertFalse(
            $attributes['appsignal.config.send_request_session_data'],
        );
    }

    public function testSendRequestSessionDataTrueNotSet(): void
    {
        $config = new Config(sendRequestSessionData: true);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertArrayNotHasKey(
            'appsignal.config.send_request_session_data',
            $attributes,
        );
    }

    public function testRevision(): void
    {
        $config = new Config(revision: 'abc123');
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame('abc123', $attributes['appsignal.config.revision']);
    }

    public function testNullRevisionNotSet(): void
    {
        $config = new Config(revision: null);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertArrayNotHasKey('appsignal.config.revision', $attributes);
    }

    public function testHostname(): void
    {
        $config = new Config(hostname: 'my-host');
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame('my-host', $attributes['host.name']);
    }

    public function testNullHostnameNotSet(): void
    {
        $config = new Config(hostname: null);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertArrayNotHasKey('host.name', $attributes);
    }

    public function testServiceName(): void
    {
        $config = new Config(serviceName: 'my-service');
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertSame('my-service', $attributes['service.name']);
    }

    public function testNullServiceNameNotSet(): void
    {
        $config = new Config(serviceName: null);
        $attributes = new OpenTelemetryResourceAttributes($config)->toArray();

        $this->assertArrayNotHasKey('service.name', $attributes);
    }
}
