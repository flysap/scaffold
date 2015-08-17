<?php

namespace Flysap\Scaffold;

abstract class Builder {

    const DEFAULT_TYPE_ELEMENT = 'text';

    protected $source;

    public function __construct($source) {
        $this->setSource($source);
    }

    /**
     * Set source .
     *
     * @param $source
     * @return $this
     */
    public function setSource($source) {
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

    public function __toString() {
        return $this->render();
    }
}