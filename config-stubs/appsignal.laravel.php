<?php

return [
    'name' => env('APPSIGNAL_APP_NAME', env('APP_NAME', 'App')),
    'environment' => env('APP_ENV', 'production'),
    'push_api_key' => env('APPSIGNAL_PUSH_API_KEY'),
    'collector_url' => env('APPSIGNAL_COLLECTOR_URL'),
    'disable_patches' => [],
];
