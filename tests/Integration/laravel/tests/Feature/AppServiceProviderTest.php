<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_config_stub_uses_correct_paths(): void
    {
        $configPath = config_path('appsignal.php');
        $backupPath = config_path('appsignal.backup.php');

        rename($configPath, $backupPath);

        try {
            $this->artisan('vendor:publish', ['--tag' => 'appsignal'])
                // this assertion isn't using `vendor/appsignal/appsignal-php`
                // because we add the package as a path based repo
                ->expectsOutputToContain("Copying file [/package/config-stubs/appsignal.laravel.php] to [config/appsignal.php]")
                ->assertExitCode(0);
        } finally {
            if (file_exists($configPath)) {
                unlink($configPath);
            }
            rename($backupPath, $configPath);
        }
    }
}
