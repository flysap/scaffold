<?php

namespace Flysap\Scaffold;

use Flysap\Support;
use Modules;

class ScaffoldService {

    public function lists($model) {
        $eloquent = $this->getModel($model);

        $form     = $this->getBuilder($eloquent);

        return view('scaffold::scaffold.lists', compact('form'));
    }

    public function create($model) {
        $eloquent = $this->getModel($model);

        $form     = $this->getBuilder($eloquent);

        return view('scaffold::scaffold.create', compact('form'));
    }

    public function update($model, $id) {
        $eloquent = $this->getModel($model, $id);

        $form     = $this->getBuilder($eloquent);

        return view('scaffold::scaffold.edit', compact('form'));
    }


    /**
     * Get file instance ..
     *
     * @param $file
     * @param null $identificator
     * @return mixed
     */
    private function getModel($file, $identificator = null) {
        $spaces = $this->getSpaces();
        list($vendor, $user, $model) = explode('/', $file);

        foreach ($spaces as $space) {
            $full = $space . DIRECTORY_SEPARATOR . $vendor . DIRECTORY_SEPARATOR . $user . DIRECTORY_SEPARATOR . ucfirst($model) . '.php';

            if( ! Support\is_path_exists(
                app_path('../' . $full)
            ) )
                continue;

            if( ! Support\has_extension($full, 'php'))
                continue;

            require_once(app_path('../' . $full));

            #@todo check psr4 ..
            $class = 'Modules\\' . $vendor . '\\' . $user . '\\' . ucfirst($model);

            if(! is_null($identificator))
                $model = $class::find($identificator);
            else
                $model = new $class;
        }

        return $model;
    }

    /**
     * Get namespaces .
     *
     * @return mixed
     */
    private function getSpaces() {
        return config('scaffold.model_namespaces');
    }

    /**
     * Get builder .
     *
     * @param null $eloquent
     * @return \Illuminate\Foundation\Application|mixed
     */
    private function getBuilder($eloquent = null) {
        $builder = app('form-builder');

        if(! $eloquent)
            return $builder->fromEloquent($eloquent);

        return $builder;
    }

}