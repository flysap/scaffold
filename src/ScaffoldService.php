<?php

namespace Flysap\Scaffold;

use Cartalyst\Tags\TaggableInterface;
use Eloquent\ImageAble\ImageAble;
use Eloquent\Meta\MetaAble;
use Parfumix\TableManager;
use Flysap\Scaffold\Builders\Eloquent;
use Flysap\Support;
use Illuminate\Http\Request;
use Laravel\Meta\Eloquent\MetaSeoable;
use Modules;
use Input;
use DataExporter;

class ScaffoldService {

    public function lists($model) {
        $eloquent = $this->getModel($model);

        $params = Input::all();

        $table = TableManager\table($eloquent, 'eloquent', ['class' => 'table table-hover']);

        $scopes = (new Scopes)
            ->addScopes(
                in_array('scopes', get_class_methods(get_class($eloquent))) ? $eloquent->scopes() : []
            )->setSource($eloquent);

        /**
         * If scope was sent that filter current table by current scope .
         */
        if( isset($params['scope']) ) {
            if( $scopes->hasScope($params['scope']) ) {
                $scope = $scopes->getScope($params['scope']);

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
        #@todo for default smart search is disabled, need an package wich will do that smart search .
        if(! $this->isEnabledSmartSearch())
            $table->filter(function($query) use($params, $table) {
                $eloquent         = $table->getDriver()->getSource()->getModel();
                $availableFilters = $eloquent->skyFilter();

                foreach ($availableFilters as $key => $options) {
                    if( isset($params[$key]) && ( !empty($params[$key]) ) ) {

                        $type = $options;
                        if( is_array($options) )
                            $type = $options['type'];

                        /** If there is custom query than run it . */
                        if( is_array($options) && isset($options['query']) && ( $options['query'] instanceof \Closure ) ) {
                            $custom = $options['query'];

                            $query = $custom($query, $params[$key]);
                        } else {
                            if( $type == 'select' || $type == 'checkbox' )
                                $query = $query->where($key, $params[$key]);
                            else
                                $query = $query->where($key, 'LIKE', '%'.$params[$key].'%');
                        }
                    }
                }

                return $query;
            });
        else
            $table->filter(function($query) use($params, $table) {
                $eloquent         = $table->getDriver()->getSource()->getModel();
                $availableFilters = $eloquent->skyFilter();

                foreach ($availableFilters as $key => $options)
                    if( isset($params[$key]) && ( !empty($params[$key]) ) )
                        $query = $eloquent->search(
                            $params[$key]
                        );

                return $query;
            });

        /** @var Get exporters . $exporters */
        $exporters = config('scaffold.exporters');
        if( in_array('exporters', get_class_methods(get_class($eloquent))) )
            $exporters = call_user_func([$eloquent, 'exporters']);

        $exporters = (new Exporters)
            ->setExporters($exporters);

        /** Export if . */
        if( isset($params['export']) ) {
            $exporter = $params['export'];

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

        return view('scaffold::scaffold.lists', compact('table', 'scopes', 'exporters', 'model'));
    }

    public function create($model) {
        $eloquent = $this->getModel($model);

        $form = (new Eloquent($eloquent))
            ->build();

        return view('scaffold::scaffold.create', compact('form'));
    }

    public function update($model, $id) {
        $eloquent = $this->getModel($model, $id);

        $params = Input::all();

        if( isset($params['export']) ) {
            $availableExporters = DataExporter\get_exporters();

            if(! array_key_exists($params['export'], $availableExporters))
                return redirect()
                    ->back();

            $driver = (new DataExporter\Drivers\Collection(
                $eloquent->toArray()
            ));

            return DataExporter\download(
                $params['export'],
                $driver
            );
        }

        $form = (new Eloquent($eloquent))
            ->build(['method' => 'post', 'enctype' => 'multipart/form-data', 'action' => '']);

        if( $_POST ) {
            #if( ! $form->isValid($params) )
                #throw new ScaffoldException(_('Validation failed'));

            if( $eloquent instanceof MetaAble )
                $eloquent->syncMeta(isset($params['meta']) ? $params['meta'] : []);

            if( $eloquent instanceof MetaSeoable ) {
                if( isset($params['seo']) )
                    $eloquent->storeSeo($params['seo']);
            }


            /**
             * By default we will upload image through some of filters .
             */
            if( $eloquent instanceof ImageAble ) {
                if( isset($params['images']) ) {
                    $behaviors = [];

                    /**
                     * When image is uploaded we have for the first to check if the user has custom configurations for uploading images.
                     *  if there persist some image filters we have walk through that filters.
                     *   additionally we can set custom store path for images or event set an placeholder for image name .
                     */

                    if( isset($eloquent['behaviors']) )
                        $behaviors = $eloquent['behaviors'];

                    if( in_array('behaviors', get_class_methods(get_class($eloquent))) )
                        $behaviors = $eloquent->behaviors();


                    /** @var Check for filters . $filters */
                    $filters = [];
                    if( isset($behaviors['filters']) )
                        $filters = $behaviors['filters'];


                    /** @var Check for path . $path */
                    $path = null;
                    if( isset($behaviors['path']) )
                        $path = public_path($behaviors['path']);


                    /** @var Check for closure . $closure */
                    $closure = null;
                    if( isset($behaviors['closure']) && ( $behaviors['closure'] instanceof \Closure ) )
                        $closure = $behaviors['closure'];


                    /** @var Check for custom placeholder . $placeholder */
                    $placeholder = null;
                    if( isset($behaviors['placeholder']) ) {
                        $placeholder = $behaviors['placeholder'];

                        $availablePlaceholders = array_merge(
                            $eloquent->getAttributes(),
                            ['date' => date('Y.m.d')],
                            isset($behaviors['available']) ? $behaviors['available'] : []
                        );

                        foreach ($availablePlaceholders as $key => $value)
                            $placeholder = str_replace('%'.$key.'%', $value, $placeholder);
                    }

                    $eloquent->upload($params['images'], $path, $filters, $placeholder, $closure);
                }
            }

            if( $eloquent instanceof TaggableInterface ) {
                if( isset($params['tags']) )
                    $eloquent->setTags($params['tags']);
            }

            $eloquent->fill($params)
             #   ->refresh($params)
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
     * @param Request $params
     */
    public function custom(Request $params) {
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

    /**
     * Check if is enabled smart search .
     *
     * @return bool
     */
    protected function isEnabledSmartSearch() {
        $isEnabled = false;

        if( isset(config('scaffold')['smart_search']) && config('scaffold')['smart_search'] == true )
            $isEnabled = true;

        return $isEnabled;
    }

}