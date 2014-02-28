<?php

namespace Oro\Bundle\MigrationBundle\Migration;

class QueryBag
{
    /**
     * @var array
     */
    protected $preSqls = [];

    /**
     * @var array
     */
    protected $postSqls = [];

    /**
     * Gets a list of SQL queries should be executed before UP migrations defined in this class
     *
     * @return array
     */
    public function getPreSqls()
    {
        return $this->preSqls;
    }

    /**
     * Gets a list of SQL queries should be executed after UP migrations defined in this class
     *
     * @return array
     */
    public function getPostSqls()
    {
        return $this->postSqls;
    }

    /**
     * Register a SQL query should be executed before UP migrations defined in this class
     *
     * @param string $sql
     */
    public function addPreSql($sql)
    {
        $this->preSqls[] = $sql;
    }

    /**
     * Register a SQL query should be executed after UP migrations defined in this class
     *
     * @param string $sql
     */
    public function addPostSql($sql)
    {
        $this->postSqls[] = $sql;
    }

    /**
     * Register a SQL query should be executed after UP migrations defined in this class
     * This method is just an alias for addPostSql
     *
     * @param string $sql
     */
    public function addSql($sql)
    {
        $this->postSqls[] = $sql;
    }

    /**
     * Clears all data in this bag
     */
    public function clear()
    {
        if (!empty($this->preSqls)) {
            $this->preSqls = [];
        }
        if (!empty($this->postSqls)) {
            $this->postSqls = [];
        }
    }

    /**
     * Gets a SQL query can be used to rename a table
     *
     * @param string $oldTableName
     * @param string $newTableName
     * @return string
     */
    public function getRenameTableSql($oldTableName, $newTableName)
    {
        return sprintf(
            'ALTER TABLE %s RENAME TO %s;',
            $oldTableName,
            $newTableName
        );
    }
}
