<?php

namespace Tests;

use Appsignal\Config;
use Appsignal\Testing\CapturesTelemetry;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CapturesTelemetry;

    protected function appsignalConfig(): ?Config
    {
        return new Config(
            name: config('appsignal.name'),
            environment: config('appsignal.environment'),
            pushApiKey: config('appsignal.push_api_key'),
            collectorEndpoint: config('appsignal.collector_endpoint'),
            filterAttributes: config('appsignal.filter_attributes', []),
            filterFunctionParameters: config('appsignal.filter_function_parameters', []),
            filterRequestQueryParameters: config('appsignal.filter_request_query_parameters', []),
            filterRequestPayload: config('appsignal.filter_request_payload', []),
            filterRequestSessionData: config('appsignal.filter_request_session_data', []),
            ignoreActions: config('appsignal.ignore_actions', []),
            ignoreErrors: config('appsignal.ignore_errors', []),
            ignoreNamespaces: config('appsignal.ignore_namespaces', []),
            requestHeaders: config('appsignal.request_headers'),
            responseHeaders: config('appsignal.response_headers'),
            sendFunctionParameters: config('appsignal.send_function_parameters'),
            sendRequestQueryParameters: config('appsignal.send_request_query_parameters'),
            sendRequestPayload: config('appsignal.send_request_payload'),
            sendRequestSessionData: config('appsignal.send_request_session_data'),
        );
    }
}
