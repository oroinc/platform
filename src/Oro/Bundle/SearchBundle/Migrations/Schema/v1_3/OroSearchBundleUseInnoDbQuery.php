<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema\v1_3;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class OroSearchBundleUseInnoDbQuery implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        return 'Use InnoDB for MySQL >= 5.6';
    }

    /**
     * {@inheritDoc}
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

    protected function getTableName(): string
    {
        return 'oro_search_index_text';
    }
}
