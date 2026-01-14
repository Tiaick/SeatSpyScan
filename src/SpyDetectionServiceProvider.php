<?php

declare(strict_types=1);

namespace Seat\SpyDetection;

use Illuminate\Support\ServiceProvider;
use Seat\SpyDetection\Console\SpyDetectionScanCommand;
use Seat\SpyDetection\Engine\SpyCheckRunner;
use Seat\SpyDetection\Support\SpyDetectionMenu;

class SpyDetectionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/spy-detection.php', 'spy-detection');
        $this->publishes([
            __DIR__ . '/../config/spy-detection.php' => config_path('spy-detection.php'),
        ], 'spy-detection-config');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'spy-detection');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([SpyDetectionScanCommand::class]);
        }

        $this->registerPermissions();
        SpyDetectionMenu::register();
    }

    public function register(): void
    {
        $this->app->singleton(SpyCheckRunner::class, function () {
            return new SpyCheckRunner();
        });
    }

    private function registerPermissions(): void
    {
        if (!class_exists(\Seat\Services\Configuration\Permission::class)) {
            return;
        }

        try {
            \Seat\Services\Configuration\Permission::add(
                'spy-detection.view',
                'Spy Detection',
                'Allows access to the Spy Detection screen'
            );
        } catch (\Throwable) {
            // Permission registration should not block boot.
        }
    }
}
