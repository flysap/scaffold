<?php

namespace Flysap\Scaffold\Builders;

use Eloquent\Translatable\Translatable;
use Flysap\Scaffold\BuildAble;
use Flysap\Scaffold\Builder;
use Flysap\Scaffold\ScaffoldAble;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Parfumix\FormBuilder;
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
        $elements = [];

        $elements = $this->processFields($elements);
        $elements = $this->processRelations($elements);
        $elements = $this->getAppliedPackages($elements);

        $form = new FormBuilder\Form($params);
        $form->setElements(
            $elements, true
        );

        return $form;
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
        if ($this->hasRelations())
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

        array_walk($relations, function ($attributes, $relation) use (& $elements) {
            if (! is_array($attributes)) {
                $relation = $attributes;
                $attributes = [];
            }

            if (! method_exists($this->getSource(), $relation))
                return false;

            $query = $this->getSource()->{$relation}();

            /**
             * If there persist custom query to extract relation data will be applied that query .
             */
            if (isset($attributes['query']))
                if (($queryClosure = $attributes['query']) && $queryClosure instanceof \Closure)
                    $query = $queryClosure($query);

            $items = $query->get();

            /** Get editable fields for related model . */
            if( ! isset($attributes['fields']) ) {
                $related = $query->getRelated();

                $fields = $related->getFillable();
                if( $related instanceof ScaffoldAble )
                    $fields = $related->skyEdit();
            } else {
                $fields = $attributes['fields'];
            }


            if( $query instanceof HasOne ) {
                $item = $items->first();

                if( $item ) {

                    /**
                     * If there is value we have to show the id to add possibility to edit that value .
                     */
                    $hidden = FormBuilder\get_element('hidden', $attributes + [
                        'value' => $item->{$query->getRelated()->getKeyName()},
                        'group' => str_singular($relation)
                    ]);

                    $hidden->name(
                        $relation . '[' . $query->getRelated()->getKeyName() . ']'
                    );

                    array_push($elements, $hidden);
                }

                foreach($fields as $field => $attributesField) {
                    if (is_string($attributesField)) {
                        $attributesField = ['type' => $attributesField];
                    } elseif (! is_array($attributesField)) {
                        $field = $attributesField;
                        $attributesField = [];
                    }

                    if( $field == $query->getPlainForeignKey() )
                        continue;

                    if (! isset($attributesField['label']))
                        $attributesField['label'] = ucfirst($field);

                    if (! isset($attributesField['group']))
                        $attributesField['group'] = str_singular($relation);

                    if ($attributesField instanceof \Closure)
                        $attributesField = $attributesField();
                    else
                        $attributesField = array_merge($attributesField, $attributes);

                    $element = $this->getElementInstance(
                        $field, $attributesField, $item
                    );

                    $element->name(
                        $relation . '[' . $field . ']'
                    );

                    array_push($elements, $element);
                }

            } elseif( $query instanceof HasMany ) {

                foreach ($items as $key => $item) {
                    /**
                     * If there is value we have to show the id to add possibility to edit that value .
                     */
                    $hidden = FormBuilder\get_element('hidden', $attributes + [
                        'value' => $item->{$item->getKeyName()},
                        'group' => $relation
                    ]);

                    $hidden->name(
                        $relation . '[' . $key . ']' . '[' . $item->getKeyName() . ']'
                    );

                    /**
                     * Adding sync element .
                     *
                     */
                    $sync = FormBuilder\get_element('hidden', $attributes + [
                        'value' => 1
                    ]);

                    $sync->name(
                        $relation . '[' . $key . ']' . '[sync]'
                    );

                    array_push($elements, $hidden);
                    array_push($elements, $sync);

                    /**
                     * Go through values and extract them .
                     */
                    foreach ($fields as $field => $attributesField) {
                        if (is_string($attributesField)) {
                            $attributesField = ['type' => $attributesField];
                        } elseif (! is_array($attributesField)) {
                            $field = $attributesField;
                            $attributesField = [];
                        }



                        if( $field == $query->getPlainForeignKey() )
                            continue;

                        if (! isset($attributesField['label']))
                            $attributesField['label'] = ucfirst($field);

                        if (! isset($attributesField['group']))
                            $attributesField['group'] = strtolower($relation);

                        if ($attributesField instanceof \Closure)
                            $attributesField = $attributesField();
                        else
                            $attributesField = array_merge($attributesField, $attributes);

                        $element = $this->getElementInstance(
                            $field, $attributesField, $item
                        );

                        $element->name(
                            $relation . '[' . $key . ']' . '[' . $field . ']'
                        );

                        array_push($elements, $element);
                    }
                }

                foreach ($fields as $field => $attributesField) {
                    if (is_string($attributesField)) {
                        $attributesField = ['type' => $attributesField];
                    } elseif (! is_array($attributesField)) {
                        $field = $attributesField;
                        $attributesField = [];
                    }

                    $attributesField['disabled'] = 'disabled';

                    if( $field == $query->getPlainForeignKey() )
                        continue;

                    if (! isset($attributesField['label']))
                        $attributesField['label'] = ucfirst($field);

                    if (! isset($attributesField['group']))
                        $attributesField['group'] = strtolower($relation);

                    if ($attributesField instanceof \Closure)
                        $attributesField = $attributesField();
                    else
                        $attributesField = array_merge($attributesField, $attributes);

                    if(! isset($attributesField['value']))
                        $attributesField['value'] = '';

                    $element = $this->getElementInstance(
                        $field, $attributesField
                    );

                    $key = $items->count();
                    $element->name(
                        $relation . '[' . $key . ']' . '[' . $field . ']'
                    );

                    array_push($elements, $element);
                }

                array_push($elements, FormBuilder\element_custom([
                    'value' => '<a href="#" onClick="$(this).closest(\'div.tab-pane\').find(\'input:disabled\').each(function(k, v) { $(this).removeAttr(\'disabled\') })">Add new</a>',
                    'group' => strtolower($relation)
                ]));

            } elseif( $query instanceof BelongsTo ) {
                $selected = $items->first();

                $value = null;
                if($selected)
                    $value = $selected->{$query->getRelated()->getKeyName()};

                $keys = array_keys($fields);
                $firstField = array_pop($keys);

                $options = [];

                $results = $query->getRelated()
                    ->get();

                foreach($results as $source) {

                    $firstValue  = null;
                    $secondValue = $source->getAttribute($query->getRelated()->getKeyName());
                    if( isset($fields[$firstField]['translatable']) && $fields[$firstField]['translatable'] === true ) {
                        if( $source instanceof Translatable ) {
                            if( $translation = $source->translate( isset($fields[$firstField]['locale']) ? $fields[$firstField]['locale'] : null ) )
                                $firstValue = $translation->$firstField;
                        }
                    } else {
                        $firstValue = $source->getAttribute($firstField);
                    }

                    $options[$secondValue] = $firstValue;
            }

                $select = FormBuilder\element_select(ucfirst($relation), [
                    'options' => [null => '--Select--'] + $options,
                    'value' => $value,
                    'group' => $relation
                ]);

                $select->name(
                    $relation . '[' . $query->getRelated()->getKeyName() . ']'
                );

                array_push($elements, $select);
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
        return count($this->getSource()->skyEdit());
    }

    /**
     * Get fields .
     *
     * @return mixed
     */
    protected function getFields() {
        return $this->getSource()->skyEdit();
    }

    /**
     * Process fields .
     *
     * @param array $elements
     * @return array
     */
    protected function processFields($elements = array()) {
        $fields = $this->getFields();

        array_walk($fields, function ($attributes, $key) use (& $elements) {
            if (is_string($attributes)) {
                $attributes = ['type' => $attributes];
            } elseif (! is_array($attributes)) {
                $key = $attributes;
                $attributes = [];
            }

            if (! isset($attributes['label']))
                $attributes['label'] = ucfirst($key);

            $element = $this->getElementInstance($key, $attributes);

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
        if (is_null($source))
            $source = $this->getSource();

        return $this->hasCasts($attribute, $source) ? $source->casts[$attribute] : null;
    }

    /**
     * Check if field in casts exits .
     *
     * @param $attribute
     * @param null $source
     * @return bool
     */
    protected function hasCasts($attribute, $source = null) {
        if (is_null($source))
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
        if (is_null($source))
            $source = $this->getSource();

        if (isset($attributes['type']))
            $type = $attributes['type'];
        elseif ($this->hasCasts($key, $source))
            $type = $this->getCasts($key, $source);
        else
            $type = self::DEFAULT_TYPE_ELEMENT;

        if (! isset($attributes['value'])) {
            if (array_key_exists($key, $source->getAttributes()))
                $attributes['value'] = $source->{$key};
        }

        if (! isset($attributes['name']))
            $attributes['name'] = $key;

        return FormBuilder\get_element($type, $attributes);
    }
}