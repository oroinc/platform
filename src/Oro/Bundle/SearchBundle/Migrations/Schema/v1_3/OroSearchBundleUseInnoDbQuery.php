<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class OroSearchBundleUseInnoDbQuery implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @inheritdoc
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Gets a query description
     * If this query has several sub queries you can return an array of descriptions for each sub query
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Use InnoDB for MySQL >= 5.6';
    }

    /**
     * Executes a query
     *
     * @param LoggerInterface $logger A logger which can be used to log details of an execution process
     */
    public function execute(LoggerInterface $logger)
    {
        $driverName = $this->connection->getDriver()->getName();
        if (in_array($driverName, ['pdo_mysql', 'mysqli'], true)) {
            $version = $this->connection->fetchColumn('select version()');
            if (version_compare($version, '5.6.0', '>=')) {
                $query = sprintf('ALTER TABLE `%s` ENGINE = INNODB;', $this->getTableName());
                $logger->info($query);
                $this->connection->executeQuery($query);
            }
        }
    }

    /**
     * @return string
     */
    protected function getTableName()
    {
        return 'oro_search_index_text';
    }
}
