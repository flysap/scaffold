<?php

namespace Flysap\Scaffold;

use Flysap\FormBuilder\Contracts\FieldInterface;
use Flysap\FormBuilder\Form;
use Flysap\Scaffold\Exceptions\FormException;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Flysap\Support;

/**
 * Class Form
 * @package Flysap\FormBuilder
 */
class FormBuilder {

    /**
     * @var
     */
    protected $elements;

    /**
     * @var string
     */
    protected $defaultField = 'text';

    /**
     * @var Repository
     */
    private $formConfigurations;

    public function __construct(Repository $formConfigurations) {
        $this->formConfigurations = $formConfigurations;
    }

    /**
     *  Create elements from array .
     *
     * @param array $array
     * @param ScaffoldAble $eloquent
     * @param array $attributes
     * @return $this
     * @throws FormException
     */
    public function fromArray(array $array, ScaffoldAble $eloquent = null, array $attributes = array()) {
        foreach ($array as $key => $field) {
            if(! is_string($key))
                $this->processSingleKey($field, $eloquent);
            elseif( $field instanceof FieldInterface )
                $this->elements[$key] = $field;
            elseif( is_string($key) )
                $this->processArrayKey($key, $field, $eloquent);
            else
                throw new FormException(
                    _("Invalid element type")
                );
        }

        return new Form([
            'elements' => $this->elements,
        ] + $attributes);
    }

    /**
     * @param ScaffoldAble $eloquent
     * @param array $attributes
     * @return FormBuilder
     * @throws FormException
     */
    public function fromEloquent(ScaffoldAble $eloquent, array $attributes = array()) {
        $fields = $eloquent->scaffoldEditable();

        return $this->fromArray($fields, $eloquent, $attributes);
    }

    /**
     * Render elements from file
     *
     * @param $pathToFile
     * @param ScaffoldAble $eloquent
     * @param array $attributes
     * @return $this
     * @throws FormException
     */
    public function fromFile($pathToFile, ScaffoldAble $eloquent, array $attributes = array()) {
        if (! Support\is_path_exists(
            config_path($pathToFile)
        ))
            throw new FormException(_("Invalid path config file"));

        $array = require config_path($pathToFile);

        return $this->fromArray(
            $array, $eloquent, $attributes
        );
    }



    /**
     * Process single key .
     *
     * @param $field
     * @param Model $eloquent
     * @param bool $isRelation
     * @return mixed
     * @throws FormException
     */
    protected function processSingleKey($field, Model $eloquent = null, $isRelation = false) {
        $value = null;

        if(! is_null($eloquent)) {
            $alias = isset($eloquent->casts[$field]) ? $eloquent->casts[$field] : $this->defaultField;

            $value = $eloquent->getAttribute($field);

            if( $value instanceof Collection ) {
                foreach ($value as $eloquent) {
                    $field = str_singular($eloquent->getTable());

                    $this->processSingleKey($field, $eloquent, true);
                }

                return;
            }
        } else {
            $alias = $field;
        }

        $class = $this->getAliasField($alias);

        $attributes = [];
        if( $isRelation ) {
            $name = $isRelation ? sprintf('%s[%s]', $field, $eloquent->id) : $field;

            $attributes = [
                'group' => $field
            ];
        } else {
            $name = $field;
        }

        $attributes = array_merge([
            'name'   => $name,
            'value'  => $value,
        ], $attributes);

        $instance = new $class($attributes);

        if( $isRelation )
            $this->elements[$field .'_'. $eloquent->id] = $instance;
        else
            $this->elements[$field] = $instance;

        return $this;
    }

    /**
     * Process array key .
     *
     * @param $key
     * @param $field
     * @param Model $eloquent
     * @param bool $isRelation
     * @return $this
     * @throws FormException
     */
    protected function processArrayKey($key, $field, Model $eloquent = null, $isRelation = false) {
        $value = null;

        if (! is_null($eloquent)) {
            $value = $eloquent->getAttribute($key);

            if( is_array($field) ) {
                if( isset($field['value']) && $field['value'] instanceof \Closure )
                    $value = $field['value']($eloquent->{$key}, $eloquent);
                elseif( isset($field['value']) && $field['value'] )
                    $value = isset($field['value']) ? $field['value'] : $value;
            }

            if( $value instanceof Collection ) {
                foreach ($value as $eloquent) {
                    $this->processArrayKey($key, $field, $eloquent, true);
                }

                return;
            }


            if(! isset($eloquent->casts[$key])) {
                if( is_array( $field ) )
                    $alias = $field['type'];
                else
                    $alias = $field;
            } else {
                $alias = $eloquent->casts[$key];
            }

            $class = $this->getAliasField($alias);

            $attributes = [];
            if( $isRelation ) {
                $name = $isRelation ? sprintf('%s[%s]', $key, $eloquent->id) : $field;

                $attributes = [
                    'group' => $key
                ];

            } else {
                $name = $key;
            }

            $attributes = array_merge((array)$field, $attributes);

            $instance = new $class($attributes);

            if( $isRelation )
                $this->elements[$key .'_'. $eloquent->id] = $instance;
            else
                $this->elements[$key] = $instance;

            $instance->value($value);
            $instance->name($name);

            if( $isRelation )
                $this->elements[$key .'_'. $eloquent->id] = $instance;
            else
                $this->elements[$key] = $instance;

            return;
        }

        $value = null;
        if(! is_array($field)) {
           $alias = $field;
        } else {
            if( ! isset($field['type']) )
                throw new FormException(
                    _("Please set type")
                );

            $alias = $field['type'];

            if( isset($field['value']) )
                if( $field['value'] instanceof \Closure ) {
                    $field['value'] = $field['value']();
                }
        }

        $class    = $this->getAliasField($alias);

        $instance =  new $class($field);

        $this->elements[$key] = $instance;

        return $this;
    }


    /**
     * @return mixed
     * @throws FormException
     */
    protected function getAliasFields() {
        if (!$this->formConfigurations->has('fields'))
            throw new FormException(
                _("Invalid fields")
            );

        #@todo return null if no fields ..

        $fields = $this->formConfigurations
            ->get('fields');

        return $fields;
    }

    /**
     * Get element .
     *
     * @param $alias
     * @return mixed
     * @throws FormException
     */
    protected function getAliasField($alias) {
        $elements = $this->getAliasFields();

        if (! array_key_exists($alias, $elements)) {
            throw new FormException(
                _("Invalid element")
            );
        }

        return $this->getAliasFields()[$alias];
    }

}

