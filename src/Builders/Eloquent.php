<?php

namespace Flysap\Scaffold\Builders;

use Flysap\Scaffold\BuildAble;
use Flysap\Scaffold\Builder;
use Flysap\FormBuilder;
use Laravel\Meta;
use Localization as Locale;
use DataExporter;

class Eloquent extends Builder implements BuildAble {

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
     * Get built elements .
     *
     * @return array
     */
    public function getElements() {
        $elements = [];

        $elements = $this->processFields($elements);
        $elements = $this->processRelations($elements);
        $elements = $this->getAppliedPackages($elements);

        return $elements;
    }


    /**
     * Check if source has relations .
     *
     * @return bool
     */
    protected function hasRelations() {
        return array_key_exists('relation', get_class_vars(get_class($this->getSource())));
    }

    /**
     * Get all the relations .
     *
     * @return mixed
     */
    protected function getRelations() {
        if( $this->hasRelations() )
            return $this->getSource()->relation;

        return array();
    }

    /**
     * Process all the relations .
     * @param array $elements
     * @return array
     */
    protected function processRelations($elements = array()) {
        $relations = $this->getRelations();

        array_walk($relations, function($attributes, $relation) use(& $elements) {
            if(! is_array($attributes)) {
                $relation = $attributes; $attributes = [];
            }

            /**
             * Get the field for current relations, if there is not field in attributes we will try automatically to
             *  detect the field based on table singular mode name .
             */
            $field = isset($attributes['fields']) ? array_pull($attributes, 'fields') : str_singular($relation);

            if(! is_array($field))
                $field = (array)$field;

            if( ! method_exists($this->getSource(), $relation) )
                return false;

            $query = $this->getSource()->{$relation}();

            /**
             * If there persist custom query to extract relation data will be applied that query .
             */
            if( isset($attributes['query']) )
                if( ($queryClosure = $attributes['query']) && $queryClosure instanceof \Closure )
                    $query = $queryClosure($query);

            $items = $query->get();

            foreach($items as $key => $item) {

                /**
                 * If there is value we have to show the id to add possibility to edit that value .
                 */
                $hidden = FormBuilder\get_element('hidden', $attributes + [
                    'value' => $item->{$item->getKeyName()}
                ]);

                $hidden->name(
                    $relation .'['.$key.']'.'['.$item->getKeyName().']'
                );

                /**
                 * Adding sync element .
                 *
                 */
                $sync = FormBuilder\get_element('hidden', $attributes + [
                    'value' => 1
                ]);

                $sync->name(
                    $relation .'['.$key.']'.'[sync]'
                );

                array_push($elements, $hidden);
                array_push($elements, $sync);

                /**
                 * Go through values and extract them .
                 *
                 */
                foreach($field as $value => $valueAttr) {

                    if(! is_array($valueAttr)) {
                        $value = $valueAttr; $valueAttr = [];
                    }

                    if(! isset($valueAttr['label']))
                        $valueAttr['label'] = ucfirst($value);

                    if(! isset($valueAttr['group']))
                        $valueAttr['group'] = strtolower($value);

                    if( $valueAttr instanceof \Closure )
                        $valueAttr = $valueAttr();
                    else
                        $valueAttr = array_merge($valueAttr, $attributes);

                    $element = $this->getElementInstance(
                          $value, $valueAttr, $item
                    );

                    $element->name(
                        $relation .'['.$key.']'.'['.$value.']'
                    );

                    array_push($elements, $element);
                }

            }
        });

        return $elements;
    }


    /**
     * Check if has fields .
     *
     * @return int
     */
    protected function hasFields() {
        return count( $this->getSource()->scaffoldEditable() );
    }

    /**
     * Get fields .
     *
     * @return mixed
     */
    protected function getFields() {
        return $this->getSource()->scaffoldEditable();
    }

    /**
     * Process fields .
     *
     * @param array $elements
     * @return array
     */
    protected function processFields($elements = array()) {
        $fields = $this->getFields();

        array_walk($fields, function($attributes, $key) use(& $elements) {
            if( is_string($attributes) ) {
                $attributes = ['type' => $attributes];
            } elseif(! is_array($attributes)) {
                $key = $attributes; $attributes = [];
            }

            $element    = $this->getElementInstance($key, $attributes);

            array_push($elements, $element);
        });

        return $elements;
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
     * Get rule by key .
     *
     * @param $attribute
     * @return null
     */
    protected function getRule($attribute) {
        return $this->hasRule($attribute) ? $this->getRules()[$attribute] : null;
    }

    /**
     * Check if has rule .
     *
     * @param $attribute
     * @return bool
     */
    protected function hasRule($attribute) {
        $rules = $this->getRules();

        return in_array($attribute, array_keys($rules));
    }


    /**
     * Get for type in casts .
     *
     * @param $attribute
     * @param null $source
     * @return mixed
     */
    protected function getCasts($attribute, $source = null) {
        if(is_null($source))
            $source = $this->getSource();

        return $this->hasCasts($attribute) ? $source->casts[$attribute] : null;
    }

    /**
     * Check if field in casts exits .
     *
     * @param $attribute
     * @param null $source
     * @return bool
     */
    protected function hasCasts($attribute, $source = null) {
        if(is_null($source))
            $source = $this->getSource();

        return isset($source->casts[$attribute]);
    }


    /**
     * Get element instance .
     *
     * @param $key
     * @param $attributes
     * @param null $source
     * @return mixed
     * @throws FormBuilder\ElementException
     */
    public function getElementInstance($key, $attributes, $source = null) {
        if( is_null($source) )
            $source = $this->getSource();

        if( isset($attributes['type']) )
            $type = $attributes['type'];
        elseif( $this->hasCasts($key, $source) )
            $type = $this->getCasts($key, $source);
        else
            $type = self::DEFAULT_TYPE_ELEMENT;

        if(! isset($attributes['value'])) {
            if( array_key_exists($key, $source->getAttributes()) )
                $attributes['value'] = $source->{$key};
        }

        if(! isset($attributes['name']))
            $attributes['name'] = $key;

        return FormBuilder\get_element($type, $attributes);
    }
}