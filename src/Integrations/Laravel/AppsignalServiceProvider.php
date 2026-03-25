<?php

namespace Appsignal\Integrations\Laravel;

use Illuminate\Support\ServiceProvider;

class AppsignalServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../../config-stubs/appsignal.laravel.php' => config_path('appsignal.php'),
        ], 'appsignal');
    }
}
