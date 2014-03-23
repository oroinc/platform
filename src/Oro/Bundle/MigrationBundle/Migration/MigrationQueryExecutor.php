<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class MigrationQueryExecutor
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Sets a logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Gets a connection object this migration query executor works with
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Executes the given query
     *
     * @param string|MigrationQuery $query
     * @param bool                  $dryRun
     */
    public function execute($query, $dryRun)
    {
        if ($query instanceof MigrationQuery) {
            if ($query instanceof ConnectionAwareInterface) {
                $query->setConnection($this->connection);
            }
            if ($dryRun) {
                $descriptions = $query->getDescription();
                foreach ((array)$descriptions as $description) {
                    $this->logger->notice($description);
                }
            } else {
                $query->execute($this->logger);
            }
        } else {
            $this->logger->notice($query);
            if (!$dryRun) {
                $this->connection->executeQuery($query);
            }
        }
    }
}
