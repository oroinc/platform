<?php

namespace Oro\Bundle\InstallerBundle\Migrations\Event;

use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\Event;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class MigrationEvent extends Event
{
    protected $migrations = [];

    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function addMigration(Migration $migration, $prepend = false)
    {
        if ($prepend) {
            array_unshift($this->migrations, $migration);
        } else {
            array_push($this->migrations, $migration);
        }
    }

    public function getMigrations()
    {
        return $this->migrations;
    }

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

    protected function ensureConnected()
    {
        if (!$this->connection->isConnected()) {
            $this->connection->connect();
        }
    }
}
