<?php

return [
    'name' => $_ENV['APPSIGNAL_APP_NAME'] ?? 'IntegrationTest',
    'environment' => $_ENV['APP_ENV'] ?? 'test',
    'push_api_key' => $_ENV['APPSIGNAL_PUSH_API_KEY'] ?? 'test-key',
    'collector_url' => $_ENV['APPSIGNAL_COLLECTOR_URL'] ?? 'http://collector.test',
    'disable_patches' => [],
];
