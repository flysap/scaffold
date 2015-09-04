<?php

namespace Flysap\Scaffold\Builders;

use DataExporter\DriverAssets\Eloquent\Exportable;
use Eloquent\ImageAble\ImageAble;
use Eloquent\Meta\MetaAble;
use Flysap\Scaffold\BuildAble;
use Flysap\Scaffold\Builder;
use Flysap\FormBuilder;
use Laravel\Meta\Eloquent\MetaSeoable;
use PDO;
use Laravel\Meta;
use Localization as Locale;
use DataExporter;

/**
 * Class Eloquent
 * @package Flysap\Scaffold\Builders
 */
class Eloquent extends Builder implements BuildAble {


    /**
     * Get built elements .
     *
     * @return array
     */
    public function getElements() {
        $fields = $this->getFields();

        $elements = [];

        foreach ($fields as $key => $value) {

            $fieldName = is_numeric($key) ? $value : $key;

            if( $this->isRelation($key, $value) ) {
                list($table, $field) = $this->getRelationMeta($key, $value);

                $data = $this->getRelationData(
                    $table, $field
                );

            } else {
                $data = $this->getSource()->getAttribute(
                    $fieldName
                );
            }

            $input = $this->getInput($key, $value);


            if( $this->hasRule($fieldName) )
                $input->rules(
                    $this->getRule($fieldName)
                );

            if(! is_array($data))
                $data = (array)$data;

            foreach ($data as $value) {
                $input = clone $input;

                $input->value($value);
                $elements[] = $input;
            }
        }

        $source = $this->getSource();

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
     * Build form .
     *
     * @param array $params
     * @return FormBuilder\Form
     */
    public function build($params = array()) {
        $form = new FormBuilder\Form($params);
        $form->setElements(
            $this->getElements(), true
        );

        return $form;
    }



    /**
     * Get all fields .
     *
     * @return mixed
     */
    protected function getFields() {
        return $this->getSource()
            ->scaffoldEditable();
    }


    /**
     * Get all rules .
     *
     * @return mixed
     */
    protected function getRules() {
        return isset($this->getSource()->rules) ? $this->getSource()->rules : [];
    }

    /**
     * Check if has rule .
     *
     * @param $field
     * @return bool
     */
    protected function hasRule($field) {
        $rules = $this->getRules();

        return in_array($field, array_keys($rules));
    }

    /**
     * Get rule by key .
     *
     * @param $field
     */
    public function getRule($field) {
        if( ! $this->hasRule($field) )
            return;

        return $this->getRules()[$field];
    }


    /**
     * Check if field in casts exits .
     *
     * @param $field
     * @return bool
     */
    protected function inCasts($field) {
        return isset($this->getSource()->casts[$field]);
    }

    /**
     * Get for type in casts .
     *
     * @param $field
     * @return mixed
     */
    protected function getCasts($field) {
        return $this->getSource()->casts[$field];
    }

    /**
     * Get relation data from source .
     *
     * @param $table
     * @param $field
     * @return array
     */
    protected function getRelationData($table, $field) {
        if( in_array(str_plural($field), get_class_methods(get_class($this->getSource()))) ) {
            $data = $this->getSource()->{$table}->toArray();
        } else {
            $source_table = $this->getSource()->getTable();
            $foreign_field = $table.'.'.str_singular($source_table) . '_id';
            $local_field   = $source_table.'.id';

            \DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
            $data = \DB::table($source_table)
                ->join($table, $local_field, '=', $foreign_field)
                ->where($foreign_field, $this->getSource()->getAttribute('id'))
                ->get();
        }

        return array_map(function($value) use($field) {
            return $value[$field];
        }, $data);
    }

    /**
     * Get relation meta .
     *
     * @param $key
     * @param $value
     * @return array
     */
    protected function getRelationMeta($key, $value) {
        if( is_numeric($key) )
            $field = $value;
        else
            $field = $key;

        $data = explode('.', $field);

        return $data;
    }

    /**
     * Check if key is relation .
     *
     * @param $key
     * @param $value
     * @return int
     */
    protected function isRelation($key, $value) {
        if( is_numeric($key) )
            $field = $value;
        else
            $field = $key;

        return preg_match('/\\w+\\.{1}\\w+$/', $field);
    }


    /**
     * Get input type as object .
     *
     * @param $key
     * @param $value
     * @return mixed
     * @throws FormBuilder\ElementException
     */
    protected function getInput($key, $value) {
        $input      = null;
        $attributes = [];

        if( is_numeric($key) ) {
            /** If key is numeric i have to found the type of key in casts or set default type . */
            if( $this->inCasts($value) )
                $input = $this->getCasts($value);
            else
                $input = self::DEFAULT_TYPE_ELEMENT;
        } else {
            $input = is_array($value) ? $value['type'] : $value;

            $attributes = is_array($value) ? $value : [];
        }

        if( is_numeric($key) )
            $attributes['name'] = $value;
        else
            $attributes['name'] = $key;

        return FormBuilder\get_element($input, $attributes);
    }

}