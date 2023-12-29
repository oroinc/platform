<?php

namespace Oro\Bundle\SearchBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * The migration query to set MyIsam engine to a specific table.
 */
class UseMyIsamEngineQuery implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    private string $tableName;

    public function __construct($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Use MyIsam for specified MySQL table';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $query = sprintf('ALTER TABLE `%s` ENGINE = MYISAM;', $this->tableName);
        $logger->info($query);
        $this->connection->executeQuery($query);
    }
}
