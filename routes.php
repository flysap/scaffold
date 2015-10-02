<?php


use Illuminate\Http\Request;

Route::group(['prefix' => 'admin/scaffold', 'as' => 'scaffold::', 'middleware' => 'role:admin'], function() {

    /**
     * This is an custom url where you can send custom request for your eloquent models.
     *
     */
    Route::post('custom', ['as' => 'custom', function(Request $request) {
        return app('scaffold')
            ->custom($request);
    }]);

    Route::match(['post', 'get'],'lists/{eloquent_path}', ['as' => 'main', function($file) {
        return app('scaffold')
            ->lists($file);
    }])->where('eloquent_path', "^([a-z_\\/]+)");

    Route::match(['post', 'get'], 'create/{eloquent_path}', ['as' => 'create', function($file) {
        return app('scaffold')
            ->create($file);
    }])->where('eloquent_path', "([a-z_\\/]+)");

    Route::match(['post', 'get'], 'edit/{id}/{eloquent_path}/', ['as' => 'edit', function($id, $file) {
        return app('scaffold')
            ->update($file, $id);
    }])->where(['eloquent_path' => "^([a-z_\\/]+)", 'id' => "(\\d+)"]);

    Route::get('delete/{id}{eloquent_path}/', ['as' => 'delete', function($id, $file) {
        return app('scaffold')
            ->delete($file, $id);
    }])->where(['eloquent_path' => "^([a-z_\\/]+)", 'id' => "(\\d+)"]);
});