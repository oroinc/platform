<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\EntityConfigBundle\EntityConfig\ConfigurationHandler;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryExecutorInterface;
use Psr\Log\LoggerInterface;

/**
 * Decorates MigrationQueryExecutor->execute to pass the ConfigurationHandler to setter
 */
class DecoratorMigrationQueryExecutor implements MigrationQueryExecutorInterface
{
    private MigrationQueryExecutorInterface $migrationQueryExecutor;
    private ConfigurationHandler $configurationHandler;

    public function __construct(
        MigrationQueryExecutorInterface $migrationQueryExecutor,
        ConfigurationHandler $configurationHandler
    ) {
        $this->migrationQueryExecutor = $migrationQueryExecutor;
        $this->configurationHandler = $configurationHandler;
    }

    public function getConnection(): Connection
    {
        return $this->migrationQueryExecutor->getConnection();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->migrationQueryExecutor->setLogger($logger);
    }

    /**
     * Executes the given query
     *
     * @param string|MigrationQuery $query
     * @param bool                  $dryRun
     */
    public function execute($query, $dryRun): void
    {
        if ($query instanceof ConfigurationHandlerAwareInterface) {
            $query->setConfigurationHandler($this->configurationHandler);
        }
        $this->migrationQueryExecutor->execute($query, $dryRun);
    }
}
