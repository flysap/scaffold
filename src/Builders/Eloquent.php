<?php

namespace Flysap\Scaffold\Builders;

use Flysap\Scaffold\BuildAble;
use Flysap\Scaffold\Builder;
use Flysap\FormBuilder;
use Illuminate\Support\Collection;
use Laravel\Meta;
use Localization as Locale;
use DataExporter;

class Eloquent extends Builder implements BuildAble {


    /**
     * Get built elements .
     *
     * @return array
     */
    public function getElements() {
        $fields   = $this->getFields();
        $elements = [];


        array_walk($fields, function($field, $key) use(& $elements) {
            $attribute = is_numeric($key) ? $field : $key;

            /** If field is relation */
            $attributeField = false;
            if( preg_match('/\\w+\\.{1}\\w+$/', $attribute) )
                list($attribute, $attributeField) = explode('.', $attribute);

            $values = function($attribute) {
                $result = $this->getSource()
                    ->getAttribute($attribute);

                if(! $result instanceof Collection)
                    $result = collect([$result]);

                return $result;
            };

            foreach ($values($attribute) as $value) {
                $element = $this->getInput($key, $field);
                $element->value(( $attributeField ) ? $value[$attributeField] : $value);
                $elements[] = $element;
            }
        });

        return array_merge($elements, $this->getAppliedPackages());
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