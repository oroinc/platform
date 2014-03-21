<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Psr\Log\LoggerInterface;

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

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->processQueries($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
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
                $this->connection->executeUpdate($query[0], $query[1], $query[2]);
            }
        }
    }
}
