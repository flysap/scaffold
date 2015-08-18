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

            $unmasked[$key] = @$this->alias[$matches[1]] ?: 'text';
        }

        return $unmasked;
    }
}