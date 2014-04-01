<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        if (empty($this->queries)) {
            return '';
        } elseif (count($this->queries) === 1) {
            return $this->queries[0];
        }

        return $this->queries;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        foreach ($this->queries as $query) {
            $logger->notice($query);
            $this->connection->executeUpdate($query);
        }
    }
}
