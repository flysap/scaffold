<?php


use Flysap\Scaffold\ScaffoldInterface;
use Illuminate\Http\Request;

Route::group(['prefix' => 'admin/scaffold', 'as' => 'scaffold::', 'middleware' => 'role:admin'], function() {

    /**
     * This is an custom url where you can send custom request for your eloquent models.
     *
     */
    Route::match(['post', 'get'], 'custom/{id}/{eloquent_path}/', ['as' => 'custom', function($id, $file, Request $request) {
        return app(ScaffoldInterface::class)
            ->custom($file, $id, $request);
    }])->where(['eloquent_path' => "^([a-z_\\/]+)", 'id' => "(\\d+)"]);

    Route::match(['post', 'get'],'lists/{eloquent_path}', ['as' => 'main', function($file) {
        return app(ScaffoldInterface::class)
            ->lists($file);
    }])->where('eloquent_path', "^([a-z_\\/]+)");

    Route::match(['post', 'get'], 'create/{eloquent_path}', ['as' => 'create', function($file) {
        return app(ScaffoldInterface::class)
            ->create($file);
    }])->where('eloquent_path', "([a-z_\\/]+)");

    Route::match(['post', 'get'], 'edit/{id}/{eloquent_path}', ['as' => 'edit', function($id, $file) {
        return app(ScaffoldInterface::class)
            ->update($file, $id);
    }])->where(['eloquent_path' => "^([a-z_\\/]+)", 'id' => "(\\d+)"]);

    Route::get('delete/{id}/{eloquent_path}', ['as' => 'delete', function($id, $file) {
        return app(ScaffoldInterface::class)
            ->delete($file, $id);
    }])->where(['eloquent_path' => "^([a-z_\\/]+)", 'id' => "(\\d+)"]);
});