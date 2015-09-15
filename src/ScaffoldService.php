<?php

namespace Flysap\Scaffold;

use Flysap\TableManager;
use Flysap\Scaffold\Builders\Eloquent;
use Flysap\Support;
use Illuminate\Http\Request;
use Modules;
use Input;
use DataExporter;

class ScaffoldService {

    public function lists($model) {
        $eloquent = $this->getModel($model);

        $request = Input::all();

        $table = TableManager\table('Eloquent', $eloquent, ['class' => 'table table-hover']);

        $scopes = (new Scopes)
            ->addScopes(
                $eloquent->scopes()
            )->setSource($eloquent);

        /**
         * If scope was sent that filter current table by current scope .
         */
        if( isset($request['scope']) ) {
            if( $scopes->hasScope($request['scope']) ) {
                $scope = $scopes->getScope($request['scope']);

                if( isset($scope['query']) ) {
                    $query = $scope['query'];

                    if( $query instanceof \Closure )
                        $table->filter($query);
                }
            }
        }

        /**
         * Adding table filter .
         *
         */
        $table->filter(function($query) use($request, $table) {
            $eloquent         = $table->getDriver()->getSource()->getModel();
            $availableFilters = $eloquent->skyFilter();

            foreach ($request as $key => $value)
                if( !empty($value) && array_key_exists($key, $availableFilters) )
                    $query = $query->where($key, 'LIKE', '%'.$value.'%');

            return $query;
        });


        /** @var Get exporters . $exporters */
        $exporters = config('scaffold.exporters');
        if( in_array('exporters', get_class_methods(get_class($eloquent))) )
            $exporters = call_user_func([$eloquent, 'exporters']);

        $exporters = (new Exporters)
            ->setExporters($exporters);


        /** Export if . */
        if( isset($request['export']) ) {
            $exporter = $request['export'];

            if( ! $exporters->hasExporter($exporter) )
                return redirect()
                    ->back();

            $availableExporters = DataExporter\get_exporters();

            if(! array_key_exists($exporter, $availableExporters))
                return redirect()
                    ->back();

            $driver = (new DataExporter\Drivers\Collection(
                $table->getDriver()->getSource()->get()
                    ->toArray()
            ));

            return DataExporter\download(
                $exporter,
                $driver
            );
        }

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

        return view('scaffold::scaffold.lists', compact('table', 'scopes', 'exporters'));
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
            ->build(['method' => 'post', 'enctype' => 'multipart/form-data', 'action' => '']);

        if( $_POST ) {
            $params = Input::all();

            #if( ! $form->isValid($params) )
                #throw new ScaffoldException(_('Validation failed'));

            if( isset($params['meta']) )
                $eloquent->syncMeta($params['meta']);

            if( isset($params['seo']) )
                $eloquent->storeSeo($params['seo']);

            if( isset($params['images']) )
                $eloquent->upload($params['images']);

            $eloquent->fill($params)
                ->refresh($params)
                ->save();

            return redirect()
                ->back();
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
     * Custom requests .
     *
     * @param Request $request
     */
    public function custom(Request $request) {
        #@todo
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