<?php

namespace EggDigital\Service;

use Illuminate\Support\ServiceProvider;


class ServiceServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('eggdigital/service');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->booting(function()
        {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('EggLog', 'EggDigital\Service\Facades\EggLog');
        });

        $this->app['service\egglog'] = $this->app->share(function($app)
        {
            return new Provider\EggLogProvider;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'service\egglog',
        );
    }
}