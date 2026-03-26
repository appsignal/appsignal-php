<?php

return [
    'name' => $_ENV['APPSIGNAL_APP_NAME'] ?? $_ENV['APP_NAME'] ?? 'App',
    'environment' => $_ENV['APPSIGNAL_APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'production',
    'push_api_key' => $_ENV['APPSIGNAL_PUSH_API_KEY'] ?? null,
    'collector_endpoint' => $_ENV['APPSIGNAL_COLLECTOR_ENDPOINT'] ?? null,
    'disable_patches' => [],
];
