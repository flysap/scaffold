<?php

namespace Flysap\Scaffold;

trait ScaffoldTrait {

    /**
     * Return scaffold editable .
     *
     * @return array
     */
    public function scaffoldEditable() {
        $scaffoldColumns = app('scaffold-columns');

        $columns = $scaffoldColumns->fromEloquent($this)
            ->fields()
            ->unmask();

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
        // TODO: Implement scaffoldListing() method.
    }

}