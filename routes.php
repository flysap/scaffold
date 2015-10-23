<?php

use Flysap\Scaffold\ScaffoldInterface;

Route::group(['prefix' => 'admin/scaffold', 'as' => 'scaffold::', 'middleware' => 'role:admin'], function() {

    /**
     * This is an custom url where you can send custom request for your eloquent models.
     *
     */
    Route::match(['post', 'get'], 'custom/{id}/{eloquent_path}', ['as' => 'custom', function($id, $path, \Illuminate\Http\Request $request) {

        $eloquent = app('model-finder')
            ->resolve($path, $id);

        return app(ScaffoldInterface::class)
            ->custom($eloquent, $request);

    }])->where(['eloquent_path' => "^([a-zA-Z_\\/]+)", 'id' => "(\\d+)"]);

    Route::match(['post', 'get'],'lists/{eloquent_path}', ['as' => 'main', function($path) {

        $eloquent = app('model-finder')
            ->resolve($path);

        return app(ScaffoldInterface::class)
            ->lists($eloquent, $path);

    }])->where('eloquent_path', "^([a-z_A-Z\\/]+)");

    Route::match(['post', 'get'], 'create/{eloquent_path}', ['as' => 'create', function($path) {

        $eloquent = app('model-finder')
            ->resolve($path);

        return app(ScaffoldInterface::class)
            ->create($eloquent, $path);

    }])->where('eloquent_path', "([a-zA-Z_\\/]+)");

    Route::match(['post', 'get'], 'edit/{id}/{eloquent_path}', ['as' => 'edit', function($id, $path) {

        $eloquent = app('model-finder')
            ->resolve($path, $id);

        return app(ScaffoldInterface::class)
            ->update($eloquent, $path);

    }])->where(['eloquent_path' => "^([a-zA-Z_\\/]+)", 'id' => "(\\d+)"]);

    Route::get('delete/{id}/{eloquent_path}', ['as' => 'delete', function($id, $file) {

        $eloquent = app('model-finder')
            ->resolve($file, $id);

        return app(ScaffoldInterface::class)
            ->delete($eloquent);

    }])->where(['eloquent_path' => "^([a-zA-Z_\\/]+)", 'id' => "(\\d+)"]);
});