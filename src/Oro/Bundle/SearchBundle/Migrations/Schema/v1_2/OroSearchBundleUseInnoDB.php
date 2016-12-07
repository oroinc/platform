<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class OroSearchBundleUseInnoDB implements MigrationQuery , ConnectionAwareInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @inheritdoc
     */
    public function setConnection(Connection $connection )
    {
        $this->connection = $connection;
    }

    /**
     * Gets a query description
     * If this query has several sub queries you can return an array of descriptions for each sub query
     *
     * @return string|string[]
     */
    public function getDescription()
    {
        return "Use InnoDB for Mysql >= 5.6";
    }

    /**
     * Executes a query
     *
     * @param LoggerInterface $logger A logger which can be used to log details of an execution process
     */
    public function execute(LoggerInterface $logger)
    {
        $driverName = $this->connection->getDriver()->getName();

        if(in_array($driverName , array( 'pdo_mysql' ,'mysqli' ))) {

            $version = $this->connection->executeQuery('show variables like "version"')->fetchColumn(1);

            if(version_compare($version , '5.6.0' , '>=')) {
                $query = 'ALTER TABLE `oro_search_index_text` ENGINE = INNODB;';
                $logger->info($query);
                $this->connection->executeQuery($query);
            }
        }

    }
}
