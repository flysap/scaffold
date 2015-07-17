<?php

namespace Flysap\Scaffold;

use Flysap\Scaffold\Exceptions\ScaffoldException;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PDO;

class ColumnsInfo {

    /**
     * @var
     */
    private $connection;

    /**
     * @var
     */
    protected $table;

    /**
     * @var
     */
    protected $columns;

    /**
     * @var
     */
    protected $mask = [];

    /**
     * @var Repository
     */
    protected $configRepository;

    /**
     * @param $connection
     */
    public function __construct($connection) {

        $this->connection = $connection;

        $this->configRepository = (new Repository(config('scaffold')));

        if( $this->configRepository->has('mask') )
            $this->setMask(
                $this->configRepository->get('mask')
            );
    }

    /**
     * Get oolumns from eloquent .
     *
     * @param Model $model
     * @return $this
     */
    public function fromEloquent(Model $model) {
        $this->setTable(
            $model->getTable()
        );

        return $this;
    }

    /**
     * Get columns from table .
     *
     * @param $table
     * @return $this
     */
    public function fromTable($table) {
        $this->setTable(
            $table
        );

        return $this;
    }

    /**
     * Set table .
     *
     * @param $table
     * @return $this
     */
    protected function setTable($table) {
        $this->table = $table;

        return $this;
    }

    /**
     * Get table.
     *
     * @return mixed
     */
    protected function getTable() {
        return $this->table;
    }

    /**
     * @param array $mask
     */
    public function setMask(array $mask) {
        $this->mask = $mask;
    }

    /**
     * Get mask.
     *
     * @return array
     */
    public function getMask() {
        return $this->mask;
    }

    /**
     * Unmask fields for an mask .
     *
     * @param array $fields
     * @return array
     * @throws ScaffoldException
     */
    public function unmask(array $fields = array()) {
        if(! $mask = $this->getMask())
            throw new ScaffoldException(
                _("Invalid mask")
            );

        if(! $fields)
            $fields = $this->columns;

        if(! is_array($fields))
            $fields = (array)$fields;

        $columns = array_map(function($field) {
            $type = $field['Type'];

            $matches = [];
            foreach ($this->getMask() as $mask => $value) {
                if( preg_match("/^(".$mask.")/i", $type, $matches) )
                    break;
            }

            if( $matches )
                return $this->getMask()[$matches[1]];

        }, $fields);

        return array_filter(
            $columns
        );
    }

    /**
     * Get all the fields .
     *
     * @return mixed
     */
    public function fields() {
        if(! $this->columns) {
            $driver = $this->connection->getConfig('driver');

            if( $driver == 'mysql' )
                $query = "SHOW COLUMNS FROM %s";
            elseif( $driver == 'pgsql' )
                $query = "SELECT * FROM information_schema.columns WHERE table_name = %s";

            $query = str_replace('%s', $this->getTable(), $query);

            DB::setFetchMode(PDO::FETCH_ASSOC);
            $columns = DB::select($query);

            array_map(function($column) {
               $this->columns[$column['Field']] = $column;
            }, $columns);
        }

        return $this;
    }

    /**
     * Get all fields ..
     *
     * @return mixed
     */
    public function getFields() {
        $this->fields();

        return $this->columns;
    }

    /**
     * Get field .
     *
     * @param $field
     * @return mixed
     */
    public function getField($field) {
        if(! isset($this->columns[$field]))
            return;

        return $this->columns[$field];
    }
}