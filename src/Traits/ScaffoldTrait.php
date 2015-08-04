<?php

namespace Flysap\Scaffold\Traits;

trait ScaffoldTrait {

    /**
     * @var
     */
    protected $tableFields;

    /**
     * Return scaffold editable .
     *
     * @return array
     */
    public function scaffoldEditable() {
        $columns = $this->tableFields();

        if( isset($this->fillable) )
            return array_only($columns, $this->fillable);

        return array_except($columns, array_merge(
            ['id'], $this->guarded
        ));
    }

    /**
     * Filter form fields .
     *
     * @return array|mixed
     */
    public function scaffoldFilter() {
        $columns = $this->tableFields();

        if( isset($this->filter) )
            return array_only($columns, $this->filter);

        return $columns;
    }

    /**
     * Listing form fields .
     *
     * @return array|mixed
     */
    public function scaffoldListing() {
        $columns = $this->tableFields();

        if( isset($this->list) )
            return array_merge(array_keys($columns), $this->list);

        return array_keys($columns);
    }

    /**
     * Get unmasked table fields .
     *
     * @return mixed
     */
    protected function tableFields() {
        if(! $this->tableFields) {
            $scaffoldColumns = app('scaffold-columns');

            $this->tableFields = $scaffoldColumns->fromEloquent($this)
                ->fields()
                ->unmask();
        }

        return $this->tableFields;
    }

}