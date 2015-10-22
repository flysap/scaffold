<?php

namespace Flysap\Scaffold;

use Cartalyst\Tags\TaggableInterface;
use Eloquent\ImageAble\ImageAble;
use Eloquent\Meta\MetaAble;
use Eloquent\Sortable\Sortable;
use Parfumix\TableManager;
use Flysap\Scaffold\Builders\Eloquent;
use Flysap\Support;
use Illuminate\Http\Request;
use Laravel\Meta\Eloquent\MetaSeoable;
use Modules;
use Input;
use DataExporter;
use Parfumix\FormBuilder;

class ScaffoldService implements ScaffoldInterface {

    /**
     * Lists model .
     *
     * @param $eloquent
     * @param $path
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View|mixed
     */
    public function lists($eloquent, $path) {
        $params = Input::all();

        $table = TableManager\table($eloquent, 'eloquent', ['class' => 'table table-striped table-hover', 'sortable' => ($eloquent instanceof Sortable)]);

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
        if( $this->isEnabledSmartSearch() && isset($eloquent['searchable']) )
            $table->filter(function($query) use($params, $table) {
                $eloquent = $table->getDriver()->getSource()->getModel();

                if( isset($params['search']) && !empty($params['search']) )
                    $query = $eloquent->search($params['search']);

                return $query;
            });
        else
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

        /** If eloquent implements sortable than sort . */
        if( isset($params['sortable']) ) {
            if( $eloquent instanceof Sortable ) {
                $sortableRow  = $eloquent->find($params['sortable']['id']);
                $indicatorRow = $eloquent->find($params['sortable']['element']);
                $position     = isset($params['sortable']['position']) ? $params['sortable']['position'] : 'after';

                if( ! in_array($position, ['before', 'after']) )
                    return response()
                        ->json(['success' => true]);

                if( $sortableRow && $indicatorRow )
                    $sortableRow->{$position}($indicatorRow);

                return response()
                    ->json(['success' => true]);
            }
        }

        $table->addColumn(['closure' => function($value, $attributes) use($path) {
            $elements = $attributes['elements'];

            $edit_route   = route('scaffold::edit', ['eloquent_path' => $path, 'id' => $elements['id']]);
            $delete_route = route('scaffold::delete', ['eloquent_path' => $path, 'id' => $elements['id']]);

            return <<<DOC
<a class="btn btn-default btn-flat" href="$edit_route"><i class="fa fa-edit"></i></a>
<a class="btn btn-danger btn-flat" href="$delete_route"><i class="fa fa-trash"></i></a>
DOC;
;
        }], 'Action');

        $title = str_plural(ucfirst($eloquent->getTable()));

        $widgets = in_array('widgets', get_class_methods(get_class($eloquent))) ? $eloquent->widgets() : [];

        return view('scaffold::scaffold.lists', compact('table', 'scopes', 'exporters', 'path', 'title', 'widgets'));
    }

    /**
     * Create new model .
     *
     * @param $eloquent
     * @param $path
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function create($eloquent, $path) {
        if($_POST) {
            #@todo temp can be problem if there is enabled validator .
            $eloquent = $eloquent->create($_POST);

            return $this->update($eloquent, $path);
        }

        $form = (new Eloquent($eloquent))
            ->build([
                'method' => FormBuilder\Form::METHOD_POST,
                'class' => 'form-inline'
            ]);

        return view('scaffold::scaffold.edit', compact('form'));
    }

    /**
     * Update model .
     *
     * @param $eloquent
     * @param $path
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View|mixed
     */
    public function update($eloquent, $path) {
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

        $form = (new Eloquent($eloquent, ['model' => $path, 'id' => $eloquent->id]))
            ->build(['method' => 'post', 'enctype' => 'multipart/form-data', 'action' => '', 'class' => 'form-inline']);

        if( $_POST ) {
            #if( ! $form->isValid($params) )
                #throw new ScaffoldException(_('Validation failed'));

            /** Sync meta tags . */
            if( $eloquent instanceof MetaAble )
                $eloquent->syncMeta(isset($params['meta']) ? $params['meta'] : []);

            /** Update seo tags . */
            if( $eloquent instanceof MetaSeoable )
                if( isset($params['seo']) )
                    $eloquent->storeSeo($params['seo']);

            /** Upload images */
            if( $eloquent instanceof ImageAble )
                if( isset($params['images']) )
                    $eloquent->upload($params['images']);

            /** Set tags . */
            if( $eloquent instanceof TaggableInterface )
                if( isset($params['tags']) )
                    $eloquent->setTags($params['tags']);

            #@todo on update i have to check if some fields have permissions to be rewrited .. Form->getFiltered _> ..
            $eloquent->fill($params)
                ->refresh($params)
                ->save();

            if( isset($params['save']) )
                return redirect()
                    ->back();
            elseif( isset($params['save_return']) )
                return redirect(
                    route('scaffold::main', ['eloquent_path' => $path])
                );
            else
                return redirect(
                    route('scaffold::create', ['eloquent_path' => $path])
                );
        }

        return view('scaffold::scaffold.edit', compact('form'));
    }

    /**
     * Delete model
     *
     * @param $eloquent
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete($eloquent) {
        $eloquent->delete();

        return redirect()
            ->back();
    }

    /**
     * Custom requests .
     *
     * @param $eloquent
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function custom($eloquent, Request $request) {
        $params = $request->all();

        /** If eloquent implements sortable than sort . */
        if( $request->has('images') ) {

            if( isset($params['images']['sortable']) ) {

                if( $eloquent instanceof ImageAble ) {
                    $imageClass = $eloquent->imageClass();
                    $imageRow = (new $imageClass);

                    if( $imageRow instanceof Sortable ) {
                        $sortable = $params['images']['sortable'];

                        $sortableRow  = $imageRow->find($sortable['id']);
                        $indicatorRow = $imageRow->find($sortable['element']);
                        $position     = isset($sortable['position']) ? $sortable['position'] : 'after';

                        if( ! in_array($position, ['before', 'after']) )
                            return response()
                                ->json(['success' => true]);

                        if( $sortableRow && $indicatorRow )
                            $sortableRow->{$position}($indicatorRow);

                        return response()
                            ->json(['success' => true]);
                    }
                }
            }

            if( isset($params['images']['delete']) ) {
                $imageRow = $eloquent->images()
                    ->where('id', $params['images']['delete']['id']);

                if( $imageRow )
                    $imageRow->delete();

                return response()
                    ->json(['success' => true]);
            }


            #@todo .refactor .
            if( isset($params['images']['set_main']) ) {
                $imageRow = $eloquent->images()
                    ->where('id', $params['images']['set_main']['id'])
                    ->first();

                if( $imageRow ) {
                    $eloquent->images()
                        ->update(['is_main' => 0]);

                    $imageRow->setMain();
                }

                return response()
                    ->json(['success' => true]);
            }

        }
    }

    /**
     * Check if is enabled smart search .
     *
     * @return bool
     */
    protected function isEnabledSmartSearch() {
        $isEnabled = false;

        if( isset(config('scaffold')['smart_search']) && config('scaffold')['smart_search'] === true )
            $isEnabled = true;

        return $isEnabled;
    }

}
