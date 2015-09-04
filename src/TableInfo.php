<?php

namespace Flysap\Scaffold;

use Illuminate\Database\ConnectionInterface;
use PDO;
use Flysap\Scaffold;

require_once(__DIR__ . DIRECTORY_SEPARATOR . "../helpers.php");

class TableInfo {

    protected $connection;

    protected $alias = [
        'int' => 'text',
        'enum' => 'select',
        'tinyint' => 'checkbox',
        'varchar' => 'text',
        'longtext' => 'textarea',
        'text' => 'textarea',
        'timestamp' => 'date',
        'date' => 'date'
    ];

    /**
     * Set an connection .
     *
     * @param ConnectionInterface $connection
     * @return $this
     */
    public function setConnection(ConnectionInterface $connection) {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get an connection
     *
     * @return mixed
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Get all columns ..
     *
     * @param $table
     * @return array
     */
    public function columns($table) {
        $this->getConnection()
            ->setFetchMode(PDO::FETCH_ASSOC);

        $columns = array();

        switch($this->getConnection()->getConfig('driver')) {
            case 'mysql':
                    $columns = $this->getConnection()
                        ->select('SHOW COLUMNS FROM ' .$table);
                break;
            case 'pgsql':
                    $columns = $this->getConnection()
                        ->select('SELECT * FROM information_schema.columns WHERE table_name = ' . $table);
                break;
            case 'sqlite':
                    $columns = $this->getConnection()
                        ->select('PRAGMA table_info('.$table.');');
                break;
        }

        return $columns;
    }

    /**
     * Unmask columns .
     *
     * @param $columns
     * @return array
     */
    public function unmask($columns) {
        $columns = Scaffold\array_change_key_case_recursive($columns);

        $unmasked = $matches =[];

        foreach ($columns as $column) {
            foreach ($this->alias as $key => $value)
                if( preg_match("/^(".$key.")/i", $column['type'], $matches) )
                    break;

            #@todo for sqlite | mysql
            $name = isset($column['name']) ? $column['name'] : $column['field'];

            switch($matches[1]) {
                case 'enum':
                    $options = [];
                        if( preg_match_all("/'(.*?)'/", $column['type'], $matches) )
                            $options = $matches[1];

                        $newOptions = [];
                        foreach ($options as $key => $value) {
                            $newOptions[$value] = $value;
                        }

                        $unmasked[$name] = ['type' => $this->alias['enum'], 'options' => $newOptions];
                    break;

                case 'tinyint':
                        $unmasked[$name] = ['type' => $this->alias['tinyint']];
                    break;

                default:
                        $unmasked[$name] = isset($this->alias[$matches[1]]) ? $this->alias[$matches[1]] : 'text';
                    break;
            }
        }

        return $unmasked;
    }
}