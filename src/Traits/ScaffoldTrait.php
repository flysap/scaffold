<?php

namespace Flysap\Scaffold\Traits;

trait ScaffoldTrait {

    /**
     * @var
     */
    protected $columnsTable;

    /**
     * Return scaffold editable .
     *
     * @return array
     */
    public function scaffoldEditable() {
        $columns = $this->columnsTable();

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
        $columns = $this->columnsTable();

        unset($columns['id']);

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
        $columns = $this->columnsTable();

        if( isset($this->list) )
            return array_merge(array_keys($columns), $this->list);

        return $columns;
    }

    /**
     * Get unmasked table fields .
     *
     * @return mixed
     */
    protected function columnsTable() {
        if(! $this->columnsTable) {
            $tableInfo = app('table-info')
                ->setConnection(
                    $this->getConnection()
                );

            $columns = $tableInfo->columns(
                $this->getTable()
            );

            $this->columnsTable = $tableInfo->unmask($columns);
        }

        return $this->columnsTable;
    }

}