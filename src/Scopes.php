<?php

namespace Flysap\Scaffold;

use Flysap\Support\Traits\ElementPermissions;
use Illuminate\Database\Eloquent\Model;

class Scopes {

    use ElementPermissions;

    /**
     * @var
     */
    protected $scopes = [];

    /**
     * @var
     */
    protected $source;

    public function __construct() {
        $this->setScopes([
            'all' => []
        ]);
    }


    /**
     * Add additional scopes .
     *
     * @param array $scopes
     * @return $this
     */
    public function addScopes(array $scopes = []) {
        $this->scopes = array_merge($scopes, $this->scopes);

        return $this;
    }

    /**
     * Set scopes .
     *
     * @param array $scopes
     * @return $this
     */
    public function setScopes(array $scopes) {
        $this->scopes = $scopes;

        return $this;
    }

    /**
     * Get scopes .
     *
     * @return mixed
     */
    public function getScopes() {
        return $this->scopes;
    }

    /**
     * Get scope .
     *
     * @param $scope
     * @return mixed
     */
    public function getScope($scope) {
        if( $this->hasScope($scope) )
            return $this->scopes[$scope];
    }

    /**
     * Check if has scope .
     *
     * @param $scope
     * @return bool
     */
    public function hasScope($scope) {
        return isset($this->scopes[$scope]);
    }


    /**
     * Set source .
     *
     * @param $source
     * @return $this
     */
    public function setSource(Model $source) {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source .
     *
     * @return mixed
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * Run callback .
     *
     * @param callable $callback
     * @return mixed
     */
    public function runCallback(\Closure $callback) {
        return $callback($this->source->query());
    }


    /**
     * Render scopes .
     * @param null $scope
     * @param string $class
     * @return string
     */
    public function render($scope = null, $class = '') {
        $html = is_null($scope) ? '<ul class="'.$class.'">' : '';

        $scopes = is_null($scope) ? $this->getScopes() : [$scope => $this->getScope($scope)];

        array_walk($scopes, function($attributes, $scope) use(& $html) {

            /** @var Set permissions  $security */
            $security = clone $this;
            $security->roles(isset($attributes['roles']) ? $attributes['roles'] : []);
            $security->permissions(isset($attributes['permissions']) ? $attributes['permission'] : []);


            /** Set label . */
            if(! isset($attributes['label']))
                $attributes['label'] = $scope;

            $label = ucfirst($attributes['label']);


            /** Get count for current scope . */
            if( isset($attributes['query']) && $attributes['query'] instanceof \Closure )
                $query = $this->runCallback(
                    $attributes['query']
                );
            else
                $query = $this->getSource();

            /**
             * Check if we extending another scope .
             *
             */
            if( isset($attributes['extend']) ) {
                if( $this->hasScope($attributes['extend']) ) {
                    $extended = $this->getScope($attributes['extend']);

                    #if( isset($extended['query']) && $extended['query'] instanceof \Closure )
                        #$query = $extended['query']($query);
                }
            }

            $count = $query->count();


            $vars = ['scope' => $scope, 'label' => $label, 'count' => $count];


            /** @var Set template .. $template */
            $template = '<li><a href="?scope=%scope%">%label% - (%count%)</a></li>';
            if( isset($attributes['template']) )
                $template = $attributes['template'];

            foreach ($vars as $var => $value)
                $template = str_replace('%'.$var.'%', $value, $template);


            if( $security->isAllowed() )
                $html .= $template;
        });

        $html .= is_null($scope) ? '</ul>' : '';

        return $html;
    }

    /**
     * Render all the scopes .
     *
     */
    public function __toString() {
        return $this->render();
    }
}