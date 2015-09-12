<?php

namespace Flysap\Scaffold;

use Flysap\Support\Traits\ElementPermissions;

class Scopes {

    use ElementPermissions;

    /**
     * @var
     */
    protected $scopes = [];

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
    public function addScopes(array $scopes) {
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
     * Render scopes .
     * @param null $scope
     * @return string
     */
    public function render($scope = null) {
        $html = '<ul>';

        $scopes = is_null($scope) ? $this->getScopes() : [$scope];

        array_walk($scopes, function($attributes, $scope) use(& $html) {
            $security = clone $this;
            $security->roles(isset($attributes['roles']) ? $attributes['roles'] : []);
            $security->permissions(isset($attributes['permissions']) ? $attributes['permission'] : []);

            if(! isset($attributes['label']))
                $attributes['label'] = $scope;

            $label = ucfirst($attributes['label']);

            if( $security->isAllowed() ) {
                $html .= <<<DOC
<li><a href="?scope=$scope">$label</a></li>
DOC;
;
            }
        });

        $html .= '</ul>';

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