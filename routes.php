<?php

use Flysap\Scaffold\ScaffoldInterface;
use Illuminate\Http\Request;

Route::group(['prefix' => 'admin/scaffold', 'as' => 'scaffold::', 'middleware' => 'role:admin'], function() {

    /**
     * This is an custom url where you can send custom request for your eloquent models.
     *
     */
    Route::match(['post', 'get'], 'custom/{id}/{eloquent_path}/', ['as' => 'custom', function($id, $file, Request $request) {

        $eloquent = app('model-resolver')
            ->resolve($file, $id);

        return app(ScaffoldInterface::class)
            ->custom($eloquent, $request);

    }])->where(['eloquent_path' => "^([a-z_\\/]+)", 'id' => "(\\d+)"]);

    Route::match(['post', 'get'],'lists/{eloquent_path}', ['as' => 'main', function($file) {

        $eloquent = app('model-resolver')
            ->resolve($file);

        return app(ScaffoldInterface::class)
            ->lists($eloquent);

    }])->where('eloquent_path', "^([a-z_\\/]+)");

    Route::match(['post', 'get'], 'create/{eloquent_path}', ['as' => 'create', function($file) {

        $eloquent = app('model-resolver')
            ->resolve($file);

        return app(ScaffoldInterface::class)
            ->create($eloquent);

    }])->where('eloquent_path', "([a-z_\\/]+)");

    Route::match(['post', 'get'], 'edit/{id}/{eloquent_path}', ['as' => 'edit', function($id, $file) {

        $eloquent = app('model-resolver')
            ->resolve($file, $id);

        return app(ScaffoldInterface::class)
            ->update($eloquent);

    }])->where(['eloquent_path' => "^([a-z_\\/]+)", 'id' => "(\\d+)"]);

    Route::get('delete/{id}/{eloquent_path}', ['as' => 'delete', function($id, $file) {

        $eloquent = app('model-resolver')
            ->resolve($file, $id);

        return app(ScaffoldInterface::class)
            ->delete($eloquent);

    }])->where(['eloquent_path' => "^([a-z_\\/]+)", 'id' => "(\\d+)"]);
});