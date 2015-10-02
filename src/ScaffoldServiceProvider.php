<?php

namespace Flysap\Scaffold;

use Flysap\FormBuilder\FormServiceProvider;
use Parfumix\TableManager\TableServiceProvider;
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

        $this->publishes([
            __DIR__.'/../configuration' => config_path('yaml/scaffold'),
        ]);

        $this->registerMenu();
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

        $this->registerPackageServices();
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

        Support\merge_yaml_config_from(
            config_path('yaml/scaffold/general.yaml') , 'scaffold'
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

    /**
     * Register menu .
     *
     */
    protected function registerMenu() {
        $namespaces = config('scaffold.model_namespaces');

        $menuManager = app('menu-manager');

        array_walk($namespaces, function($namespace) use($menuManager) {
            $menuManager->addNamespace($namespace, false);
        });
    }

    /**
     * Register service provider dependencies .
     *
     */
    protected function registerPackageServices() {
        $providers = [
            TableServiceProvider::class,
            FormServiceProvider::class
        ];

        array_walk($providers, function($provider) {
            app()->register($provider);
        });
    }
}