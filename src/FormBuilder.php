<?php

namespace Flysap\Scaffold;

use Flysap\FormBuilder\Contracts\FieldInterface;
use Flysap\FormBuilder\Form;
use Flysap\Scaffold\Exceptions\FormException;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Form
 * @package Flysap\FormBuilder
 */
class FormBuilder {

    /**
     * @var Repository
     */
    protected $configRepository;

    /**
     * @var
     */
    protected $elements;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    protected $supportedFormats = ['php'];

    /**
     * @var string
     */
    protected $defaultField = 'text';

    /**
     * Default attributes form .
     *
     * @var array
     */
    protected $default = [
        'method'   => Form::METHOD_POST,
        'encoding' => Form::ENCTYPE_MULTIPART
    ];

    /**
     * @var Form
     */
    private $form;

    public function __construct(Filesystem $filesystem, Form $form) {
        $this->configRepository = (new Repository(config('form-builder')));
        $this->filesystem = $filesystem;
        $this->form = $form;
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

        if( ! $attributes )
            $attributes = $this->default;

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
        if (!$this->filesystem->exists(
            config_path($pathToFile)
        ))
            throw new FormException(_("Invalid path config file"));

        $pathInfo = pathinfo(
            config_path($pathToFile)
        );

        if (! array_key_exists($pathInfo['extension'], $this->supportedFormats))
            throw new FormException(_("Invalid file format"));

        $array = require_once config_path($pathToFile);

        return $this->fromArray(
            $array, $eloquent, $attributes
        );
    }


    /**
     * #@todo refactor that code .
     */

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
     * Processing value .
     *
     * @param $field
     * @param Model $eloquent
     * @return mixed
     */
    protected function processValue($field, Model $eloquent) {
        if ($field instanceof Model) {

        } elseif ($field instanceof Collection) {

        } else {
            return $eloquent->{$field};
        }
    }

    /**
     * @return mixed
     * @throws FormException
     */
    protected function getAliasFields() {
        if (!$this->configRepository->has('fields'))
            throw new FormException(
                _("Invalid fields")
            );

        #@todo return null if no fields ..

        $fields = $this->configRepository
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

