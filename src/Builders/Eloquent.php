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
        $fields   = $this->getSource()->scaffoldEditable();
        $elements = [];

        array_walk($fields, function($field, $key) use(& $elements) {
            $attribute = is_numeric($key) ? $field : $key;

            /** If field is relation */
            $relationField = false;
            if( preg_match('/\\w+\\.{1}\\w+$/', $attribute) )
                list($attribute, $relationField) = explode('.', $attribute);

            $values = function($attribute) {
                $result = $this->getSource()
                    ->getAttribute($attribute);

                if(! $result instanceof Collection)
                    $result = collect([$result]);

                return $result;
            };

            foreach ($values($attribute) as $value) {
                $element = $this->getInput($key, $field);
                $element->value(( $relationField ) ? $value[$relationField] : $value);
                $elements[] = $element;
            }
        });

        return $this->getAppliedPackages($elements);
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
    public function getRule($attribute) {
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
     * @return mixed
     */
    protected function getCasts($attribute) {
        return $this->hasCasts($attribute) ? $this->getSource()->casts[$attribute] : null;
    }

    /**
     * Check if field in casts exits .
     *
     * @param $attribute
     * @return bool
     */
    protected function hasCasts($attribute) {
        return isset($this->getSource()->casts[$attribute]);
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
            if( $this->hasCasts($value) )
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