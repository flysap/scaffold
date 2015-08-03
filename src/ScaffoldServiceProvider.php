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
        $this->loadConfiguration();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(ScaffoldServiceContract::class, function() {
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
}