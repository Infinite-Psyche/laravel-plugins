<?php
namespace Franktrue\LaravelPlugins;

use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        PluginManager::getInstance($this->app, config('plugins'));
    }

    /**
     * Setup the config.
     */
    protected function setupConfig()
    {
        $source = realpath(__DIR__.'/config.php');
        $this->publishes([$source => config_path('plugins.php')], 'laravel-plugins');
        $this->mergeConfigFrom($source, 'plugins');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->setupConfig();

        $this->app->singleton(PluginManager::class, function ($app) {
            return PluginManager::getInstance($app, config('plugins'));
        });
    }
}
