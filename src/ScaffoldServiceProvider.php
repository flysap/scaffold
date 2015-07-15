<?php

namespace Flysap\Scaffold;

use Flysap\Scaffold\Contracts\ScaffoldServiceContract;
use Illuminate\Support\ServiceProvider;

class ScaffoldServiceProvider extends Serviceprovider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(ScaffoldServiceContract::class, function() {
            return new ScaffoldService();
        });
    }
}