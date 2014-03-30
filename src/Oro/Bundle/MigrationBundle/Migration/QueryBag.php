<?php

namespace Oro\Bundle\MigrationBundle\Migration;

class QueryBag
{
    /**
     * @var array
     */
    protected $preQueries = [];

    /**
     * @var array
     */
    protected $postQueries = [];

    /**
     * Gets a list of SQL queries should be executed before UP migrations defined in this class
     *
     * @return array An SQL query can be a string or an instance of MigrationQuery
     */
    public function getPreQueries()
    {
        return $this->preQueries;
    }

    /**
     * Gets a list of SQL queries should be executed after UP migrations defined in this class
     *
     * @return array An SQL query can be a string or an instance of MigrationQuery
     */
    public function getPostQueries()
    {
        return $this->postQueries;
    }

    /**
     * Register a SQL query should be executed before UP migrations defined in this class
     *
     * @param string|MigrationQuery $query
     */
    public function addPreQuery($query)
    {
        $this->preQueries[] = $query;
    }

    /**
     * Register a SQL query should be executed after UP migrations defined in this class
     *
     * @param string|MigrationQuery $query
     */
    public function addPostQuery($query)
    {
        $this->postQueries[] = $query;
    }

    /**
     * Register a SQL query should be executed after UP migrations defined in this class
     * This method is just an alias for addPostQuery
     *
     * @param string|MigrationQuery $query
     */
    public function addQuery($query)
    {
        $this->postQueries[] = $query;
    }

    /**
     * Clears all data in this bag
     */
    public function clear()
    {
        if (!empty($this->preQueries)) {
            $this->preQueries = [];
        }
        if (!empty($this->postQueries)) {
            $this->postQueries = [];
        }
    }
}
