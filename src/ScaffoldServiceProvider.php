<?php

namespace Flysap\Scaffold;

use Flysap\Scaffold\Contracts\ScaffoldServiceContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Config\Repository;

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

        $this->app->singleton('scaffold-columns', function() {
           return new Columns(
               DB::connection()
           );
        });

        $this->app->singleton('form-builder', function($app) {
            return new FormBuilder(
                new Repository(config('form-builder'))
            );
        });
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
        $array = Yaml::parse(file_get_contents(
            __DIR__ . '/../configuration/general.yaml'
        ));

        $config = $this->app['config']->get('scaffold', []);

        $this->app['config']->set('scaffold', array_merge($array, $config));

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