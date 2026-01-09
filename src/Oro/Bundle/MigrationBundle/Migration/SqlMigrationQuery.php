<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Executes raw SQL queries during migrations.
 *
 * This class provides a simple implementation of {@see MigrationQuery} for executing one or more
 * raw SQL statements. It supports adding queries dynamically and provides descriptions
 * for logging. The class requires a database connection to be set via the {@see ConnectionAwareInterface}.
 * This is useful for migrations that need to execute SQL that cannot be expressed through
 * the schema modification API.
 */
class SqlMigrationQuery implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var string[]
     */
    protected $queries;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param string|string[] $sql
     * @throws \InvalidArgumentException if $sql is empty
     */
    public function __construct($sql = null)
    {
        if (empty($sql)) {
            $this->queries = [];
        } elseif (is_array($sql)) {
            $this->queries = $sql;
        } else {
            $this->queries   = [];
            $this->queries[] = $sql;
        }
    }

    #[\Override]
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Adds SQL query
     *
     * @param string $query The SQL query
     */
    public function addSql($query)
    {
        $this->queries[] = $query;
    }

    #[\Override]
    public function getDescription()
    {
        if (empty($this->queries)) {
            return '';
        } elseif (count($this->queries) === 1) {
            return $this->queries[0];
        }

        return $this->queries;
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        foreach ($this->queries as $query) {
            $logger->info($query);
            $this->connection->executeStatement($query);
        }
    }
}
