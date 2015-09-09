<?php

namespace Flysap\Scaffold;

use DataExporter\DriverAssets\Eloquent\Exportable;
use Eloquent\ImageAble\ImageAble;
use Eloquent\Meta\MetaAble;
use Laravel\Meta\Eloquent\MetaSeoable;
use Localization as Locale;
use Laravel\Meta;
use Flysap\FormBuilder;
use DataExporter;

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
     * Apply other packages ..
     *
     * @throws FormBuilder\ElementException
     */
    public function getAppliedPackages() {
        $source   = $this->getSource();
        $elements = [];

        /**
         * If Metaable than have meta
         *
         */
        if( $source instanceof MetaSeoable ) {
            $locales = Locale\get_locales();

            foreach($locales as $locale => $options) {
                $meta = Meta\meta_eloquent($source, $locale);

                foreach ($meta->toArray(true) as $key => $value) {
                    $elements[]  = FormBuilder\get_element('text', [
                        'name'  => 'seo['.$locale.']['.$key.']',
                        'value' => $value,
                        'group' => 'Seo',
                        'label' => $locale .' ' . ucfirst($key)
                    ]);
                }
            }
        }

        /**
         * If exportable than can download .
         */
        if( $source instanceof Exportable ) {
            $exporters = DataExporter\get_exporters();

            foreach($exporters as $exporter => $options) {
                $elements[]  = FormBuilder\get_element('link', [
                    'name'  => $exporter,
                    'group' => 'export',
                    'title' => 'Download in ' .ucfirst($exporter),
                    'href'  => ucfirst($exporter)
                ]);
            }
        }

        /**
         * If Imageable than can have images .
         *
         */
        if( $source instanceof ImageAble ) {
            $images = $source->images;

            foreach ($images as $image)
                $elements[]  = FormBuilder\get_element('image', [
                    'src'  => $image->path,
                    'title'  => $image->title,
                    'group' => 'images',
                ]);

            $elements[]  = FormBuilder\get_element('file', [
                'group' => 'images',
                'label' => 'Upload images',
                'name'  => 'images[]',
            ]);
        }

        /** if Metaable than can have meta attributes */
        if( $source instanceof MetaAble ) {
            $meta = $source->meta;

            foreach ($meta as $value) {
                $elements[]  = FormBuilder\get_element('text', [
                    'name'  => 'meta['.$value->key.']',
                    'group' => 'meta',
                    'value' => $value->value,
                    'label' => ucfirst($value->key)
                ]);
            }

        }

        return $elements;
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