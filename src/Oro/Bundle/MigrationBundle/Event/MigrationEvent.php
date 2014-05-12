<?php

namespace Oro\Bundle\MigrationBundle\Event;

use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\Event;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class MigrationEvent extends Event
{
    /**
     * @var Migration[]
     */
    protected $migrations = [];

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Adds a migration
     *
     * @param Migration $migration
     * @param bool      $prepend If TRUE a migration is added to the beginning of the list
     */
    public function addMigration(Migration $migration, $prepend = false)
    {
        if ($prepend) {
            array_unshift($this->migrations, $migration);
        } else {
            array_push($this->migrations, $migration);
        }
    }

    /**
     * Gets all migrations
     *
     * @return Migration[]
     */
    public function getMigrations()
    {
        return $this->migrations;
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array.
     *
     * @param string $sql    The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     * @return array
     */
    public function getData($sql, array $params = array(), $types = array())
    {
        $this->ensureConnected();

        return $this->connection->fetchAll($sql, $params, $types);
    }

    /**
     * Check if the given table exists in a database
     *
     * @param string $tableName
     * @return bool TRUE if a table exists; otherwise, FALSE
     */
    public function isTableExist($tableName)
    {
        $result = false;
        try {
            $this->ensureConnected();

            $result = $this->connection->isConnected()
                && (bool)array_intersect(
                    [$tableName],
                    $this->connection->getSchemaManager()->listTableNames()
                );
        } catch (\PDOException $e) {
        }

        return $result;
    }

    /**
     * Makes sure that the connection is open
     */
    protected function ensureConnected()
    {
        if (!$this->connection->isConnected()) {
            $this->connection->connect();
        }
    }
}
