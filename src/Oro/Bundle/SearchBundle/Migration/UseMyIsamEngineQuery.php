<?php

namespace Oro\Bundle\SearchBundle\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class UseMyIsamEngineQuery implements MigrationQuery, ConnectionAwareInterface
{
    /** @var Connection */
    protected $connection;

    /** @var string */
    protected $tableName;

    /**
     * @param $tableName
     */
    public function __construct($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
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
