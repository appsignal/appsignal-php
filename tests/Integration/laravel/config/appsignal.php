<?php

return [
    'name' => env('APPSIGNAL_APP_NAME', env('APP_NAME', 'IntegrationTest')),
    'environment' => env('APPSIGNAL_APP_ENV', env('APP_ENV', 'testing')),
    'push_api_key' => env('APPSIGNAL_PUSH_API_KEY', 'test-key'),
    'collector_endpoint' => env('APPSIGNAL_COLLECTOR_ENDPOINT', 'http://collector.test'),
    'disable_patches' => [],
];
