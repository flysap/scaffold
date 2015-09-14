<?php

namespace Flysap\Scaffold;

use Flysap\Support\Traits\ElementPermissions;

class Exporters {

    use ElementPermissions;

    /**
     * @var
     */
    protected $exporters = [];

    public function __construct(array $exporters = array()) {
        $this->setExporters($exporters);
    }


    /**
     * Set exporters .
     *
     * @param array $exporters
     * @return $this
     */
    public function setExporters(array $exporters) {
        $this->exporters = $exporters;

        return $this;
    }

    /**
     * Get exporters .
     *
     * @return array
     */
    public function getExporters() {
        return $this->exporters;
    }

    /**
     * Get exporter by key .
     *
     * @param $key
     * @return null
     */
    public function getExporter($key) {
        return $this->hasExporter($key) ? $this->exporters[$key] : null;
    }

    /**
     * Check if has registered exporter .
     *
     * @param $key
     * @return bool
     */
    public function hasExporter($key) {
        return isset($this->exporters[$key]) ? true : false;
    }


    /**
     * Render exporters .
     * @param null $exporter
     * @return string
     */
    public function render($exporter = null) {
        $exporters = is_null($exporter) ? $this->getExporters() : [$exporter => $this->getExporter($exporter)];

        $html = '';
        array_walk($exporters, function($attributes, $key) use(& $html) {
            /** @var Set permissions  $security */
            $security = clone $this;
            $security->roles(isset($attributes['roles']) ? $attributes['roles'] : []);
            $security->permissions(isset($attributes['permissions']) ? $attributes['permission'] : []);

            /** Set label . */
            if(! isset($attributes['label']))
                $attributes['label'] = $key;

            $label = ucfirst($attributes['label']);

            $vars = ['exporter' => $key, 'label' => $label];

            /** @var Set template .. $template */
            $template = '<a href="?export=%exporter%">%label%</a>';
            if( isset($attributes['template']) )
                $template = $attributes['template'];

            foreach ($vars as $var => $value)
                $template = str_replace('%'.$var.'%', $value, $template);

            if( $security->isAllowed() )
                $html .= $template;

        });

        return $html;

    }

    /**
     * Render all the exporters .
     *
     */
    public function __toString() {
        return $this->render();
    }
}