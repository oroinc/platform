<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Psr\Log\LoggerInterface;

/**
 * Executes parameterized SQL queries during migrations.
 *
 * This class extends {@see ParametrizedMigrationQuery} to provide a concrete implementation for
 * executing multiple SQL queries with parameter binding. It supports adding queries with
 * parameters and types, and provides descriptions of the queries for logging purposes.
 * This is useful for migrations that need to execute complex SQL with dynamic values.
 */
class ParametrizedSqlMigrationQuery extends ParametrizedMigrationQuery
{
    /**
     * @var array value = [sql, parameters, types]
     */
    protected $queries = [];

    /**
     * Adds SQL query
     *
     * @param string $query  The SQL query
     * @param array  $params The parameters to bind to the query, if any.
     * @param array  $types  The types the previous parameters are in.
     */
    public function __construct($query = null, array $params = [], array $types = [])
    {
        if (!empty($query)) {
            $this->queries[] = [$query, $params, $types];
        }
    }

    /**
     * Adds SQL query
     *
     * @param string $query  The SQL query
     * @param array  $params The parameters to bind to the query, if any.
     * @param array  $types  The types the previous parameters are in.
     */
    public function addSql($query, array $params = [], array $types = [])
    {
        $this->queries[] = [$query, $params, $types];
    }

    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->processQueries($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->processQueries($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        foreach ($this->queries as $query) {
            $this->logQuery($logger, $query[0], $query[1], $query[2]);
            if (!$dryRun) {
                $this->connection->executeStatement($query[0], $query[1], $query[2]);
            }
        }
    }
}
