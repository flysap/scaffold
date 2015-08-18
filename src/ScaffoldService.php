<?php

namespace Flysap\Scaffold;

use Flysap\Scaffold\Exceptions\ScaffoldException;
use Flysap\TableManager;
use Flysap\Scaffold\Builders\Eloquent;
use Flysap\Support;
use Modules;
use Input;

class ScaffoldService {

    public function lists($model) {
        $eloquent = $this->getModel($model);

        $request = Input::all();

        $table = TableManager\table('Eloquent', $eloquent, ['class' => 'table table-bordered table-striped dataTable']);
        $table->addColumn(['closure' => function($value, $attributes) use($model) {
            $elements = $attributes['elements'];

            $edit_route   = route('scaffold::edit', ['eloquent_path' => $model, 'id' => $elements['id']]);
            $delete_route = route('scaffold::delete', ['eloquent_path' => $model, 'id' => $elements['id']]);

            return <<<DOC
<a href="$edit_route">Edit</a><br />
<a href="$delete_route">Delete</a><br />
DOC;
;
        }], 'action');

        if( isset($request['download']) ) {
            $data = $table->convertTo($request['download']);

            return response()
                ->download($data);
        }

        return view('scaffold::scaffold.lists', compact('table'));
    }

    public function create($model) {
        $eloquent = $this->getModel($model);

        $form = (new Eloquent($eloquent))
            ->build();

        return view('scaffold::scaffold.create', compact('form'));
    }

    public function update($model, $id) {
        $eloquent = $this->getModel($model, $id);

        $form = (new Eloquent($eloquent))
            ->build(['method' => 'post', 'enctype' => 'multipart/form-data', 'action' => ' ']);

        if( $_POST ) {
            $params = Input::all();

            if( ! $form->isValid($params) )
                #throw new ScaffoldException(_('Validation failed'));

            $eloquent->fill($params)
                ->save();
        }

        return view('scaffold::scaffold.edit', compact('form'));
    }

    public function delete($model, $id) {
        $eloquent = $this->getModel($model, $id);

        $eloquent->delete();

        return redirect()
            ->back();
    }

    /**
     * Get file instance ..
     *
     * @param $file
     * @param null $identificator
     * @return mixed
     */
    private function getModel($file, $identificator = null) {
        $spaces = config('scaffold.model_namespaces');
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

}