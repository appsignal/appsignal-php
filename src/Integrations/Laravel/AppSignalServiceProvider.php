<?php

namespace AppSignal\Integrations\Laravel;

use Illuminate\Support\ServiceProvider;

class AppSignalServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../../config-stubs/appsignal.laravel.php' => config_path('appsignal.php'),
        ], 'appsignal');
    }
}
