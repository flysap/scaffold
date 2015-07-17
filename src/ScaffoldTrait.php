<?php

namespace Flysap\Scaffold;

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

    public function scaffoldFilter() {
        // TODO: Implement scaffoldFilter() method.
    }

    public function scaffoldListing() {

        /**
         * 1. check for list variable
         * 2. if not var that chec
         */
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