<?php

return [
    'active' => true,
    'name' => env('APPSIGNAL_APP_NAME', env('APP_NAME', 'IntegrationTest')),
    'environment' => env('APPSIGNAL_APP_ENV', env('APP_ENV', 'testing')),
    'push_api_key' => env('APPSIGNAL_PUSH_API_KEY', 'test-key'),
    'collector_endpoint' => env('APPSIGNAL_COLLECTOR_ENDPOINT', 'http://collector.test'),
    'disable_patches' => [],
    'filter_attributes' => ['secret', 'key'],
    'filter_function_parameters' => ['myFunc', 'secretFunc'],
    'filter_request_query_parameters' => ['password', 'token'],
    'filter_request_payload' => ['credit_card', 'ssn'],
    'filter_request_session_data' => ['session_token', 'user_id'],
    'ignore_actions' => ['GET /health', 'GET /ping'],
    'ignore_errors' => ['Illuminate\Auth\AuthenticationException'],
    'ignore_namespaces' => ['health', 'monitoring'],
];
