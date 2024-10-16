<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Executes the given query, pass the Connection to the Migration that implements ConnectionAwareInterface and
 * processing dryRun
 */
class MigrationQueryExecutor implements MigrationQueryExecutorInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Sets a logger
     */
    #[\Override]
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Gets a connection object this migration query executor works with
     */
    #[\Override]
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Executes the given query
     *
     * @param string|MigrationQuery $query
     * @param bool                  $dryRun
     */
    #[\Override]
    public function execute($query, $dryRun): void
    {
        if ($query instanceof MigrationQuery) {
            if ($query instanceof ConnectionAwareInterface) {
                $query->setConnection($this->connection);
            }
            if ($dryRun) {
                $descriptions = $query->getDescription();
                if (!empty($descriptions)) {
                    foreach ((array)$descriptions as $description) {
                        $this->logger->info($description);
                    }
                }
            } else {
                $query->execute($this->logger);
            }
        } else {
            $this->logger->info($query);
            if (!$dryRun) {
                $this->connection->executeQuery($query);
            }
        }
    }
}
