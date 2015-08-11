<?php

Route::group(['prefix' => 'scaffold', 'as' => 'scaffold::'], function() {

    Route::match(['post', 'get'],'{eloquent_path}', ['as' => 'main', function($file) {
        return app('scaffold')
            ->lists($file);
    }])->where('eloquent_path', "^([a-z\\/]+)");

    Route::get('{eloquent_path}/{id}/edit', ['as' => 'edit', function($file, $id) {
        return app('scaffold')
            ->update($file, $id);
    }])->where(['eloquent_path' => "^([a-z\\/]+)", 'id' => "(\\d+)"]);
});