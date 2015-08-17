<?php

namespace Flysap\Scaffold\Builders;

use Flysap\Scaffold\BuildAble;
use Flysap\Scaffold\Builder;
use Flysap\FormBuilder;
use PDO;

class Eloquent extends Builder implements BuildAble {


    private function getFields() {
        return $this->getSource()
            ->scaffoldEditable();
    }

    private function inCasts($field) {
        return isset($this->getSource()->casts[$field]);
    }

    private function getCasts($field) {
        return $this->getSource()->casts[$field];
    }

    public function getElements() {
        $fields = $this->getFields();

        $elements = [];

        foreach ($fields as $key => $value) {
            if( $this->isRelation($key, $value) ) {
                list($table, $field) = $this->getRelationMeta($key, $value);

                $data = $this->getRelationData(
                    $table, $field
                );

            } else {
                $data = $this->getSource()->getAttribute(
                    is_numeric($key) ? $value : $key
                );
            }

            $input = $this->getInput($key, $value);

            if( is_array($data) ) {
                foreach ($data as $value) {
                    $input = clone $input;

                    $input->value($value);
                    $elements[] = $input;
                }
            } else {
                $input->value($data);

                $elements[] = $input;
            }
        }

        return $elements;
    }

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

    protected function getRelationMeta($key, $value) {
        if( is_numeric($key) )
            $field = $value;
        else
            $field = $key;

        $data = explode('.', $field);

        return $data;
    }

    protected function isRelation($key, $value) {
        if( is_numeric($key) )
            $field = $value;
        else
            $field = $key;

        return preg_match('/\\w+\\.{1}\\w+$/', $field);
    }

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

        return FormBuilder\get_element($input, $attributes);
    }

    public function render($group = null) {
        $form = $this->build();

        return $form->render($group);
    }

    public function build() {
        $form = new FormBuilder\Form();
        $form->setElements(
            $this->getElements()
        );

        return $form;
    }
}