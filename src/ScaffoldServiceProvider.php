<?php

namespace Flysap\Scaffold;

use Illuminate\Support\ServiceProvider;
use Flysap\Support;

class ScaffoldServiceProvider extends Serviceprovider {

    /**
     * On boot's application load package requirements .
     */
    public function boot() {
        $this->loadRoutes()
            ->loadViews()
            ->loadConfiguration();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton('scaffold', function() {
            return new ScaffoldService();
        });

        $this->app->singleton('table-info', TableInfo::class);
    }

    /**
     * Load routes .
     *
     * @return $this
     */
    protected function loadRoutes() {
        if (! $this->app->routesAreCached()) {
            require __DIR__.'/../routes.php';
        }

        return $this;
    }

    /**
     * Load configuration .
     *
     * @return $this
     */
    protected function loadConfiguration() {
        Support\set_config_from_yaml(
            __DIR__ . '/../configuration/general.yaml' , 'scaffold'
        );

        return $this;
    }

    /**
     * Load views.
     *
     * @return $this
     */
    protected function loadViews() {
        $this->loadViewsFrom(__DIR__ . '/../views', 'scaffold');

        $this->publishes([
            __DIR__ . '/../views' => base_path('resources/views/vendor/scaffold'),
        ]);

        return $this;
    }
}