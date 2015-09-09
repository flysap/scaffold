<?php

namespace Flysap\Scaffold;

abstract class Builder {

    const DEFAULT_TYPE_ELEMENT = 'text';

    /**
     * @var
     */
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


    /**
     * Render form .
     *
     * @param null $group
     * @return string
     */
    public function render($group = null) {
        $form = $this->build();

        return $form->render($group);
    }

    public function __toString() {
        return $this->render();
    }


    /**
     * Building ..
     *
     * @param array $params
     * @return mixed
     */
    abstract function build($params = array());
}