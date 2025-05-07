<?php
namespace SyncPackage;

use Illuminate\Support\ServiceProvider;
use SyncPackage\Commands\SyncToCloudCommand;

class SyncServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sync.php', 'sync');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/sync.php' => config_path('sync.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncToCloudCommand::class,
            ]);
        }
    }
}
