<?php

namespace Flysap\Scaffold;

use Flysap\Scaffold\Exceptions\ScaffoldException;

class Finder {

    /**
     * @var array
     */
    protected $namespaces = array();

    /**
     * @param array $namespaces
     */
    public function __construct(array $namespaces = array()) {
        $this->addNamespaces($namespaces);
    }

    /**
     * Add path .
     *
     * @param $namespace
     * @return $this
     * @throws ScaffoldException
     */
    public function addNamespace($namespace) {
        $this->namespaces[] = $namespace;

        return $this;
    }

    /**
     * Add namespaces .
     *
     * @param array $namespaces
     * @return $this
     */
    public function addNamespaces(array $namespaces = array()) {
        array_walk($namespaces, function($namespace) {
           $this->addNamespace($namespace);
        });

        return $this;
    }

    /**
     * Get namespaces .
     *
     * @return array
     */
    public function getNamespaces() {
        return $this->namespaces;
    }

    /**
     * Resolve path .
     *
     * @param $suffix
     * @return mixed
     * @throws ScaffoldException
     */
    public function resolve($suffix, $id = null) {
        $namespaces = $this->getNamespaces();

        $resource = null;

        $suffix = str_replace('/', '\\', $suffix);

        array_walk($namespaces, function($namespace) use($suffix, & $resource, $id) {
            $class = $namespace .'\\'. $suffix;

            if( class_exists($class) ) {
                $resource = $class::findOrNew($id);
                return false;
            }
        });

        if( class_exists($suffix) && ! $resource )
            $resource = $suffix::findOrNew($id);

        if(! $resource)
            throw new ScaffoldException('Invalid class');

        if( ! $resource instanceof ScaffoldAble )
            throw new ScaffoldException('Model have to implement ScaffoldAble');

        return $resource;
    }
}