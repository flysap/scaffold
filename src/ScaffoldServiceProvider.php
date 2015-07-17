<?php

namespace Flysap\Scaffold;

use Flysap\Scaffold\Contracts\ScaffoldServiceContract;
use Illuminate\Support\Facades\DB;
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

        $this->app->singleton('scaffold-columns', function() {
           return new ColumnsInfo(
               DB::connection()
           );
        });
    }
}