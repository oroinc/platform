<?php

namespace Oro\Bundle\ApiBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\Lock\Store\StoreFactory;

class CreateBatchApiLockTable implements Migration, ConnectionAwareInterface
{
    private Connection $connection;

    /**
     * {@inheritDoc}
     */
    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /**
         * the lock table is not needed for PostgreSql database because the PostgreSql advisory locks are used
         * @see \Oro\Bundle\ApiBundle\DependencyInjection\OroApiExtension::configureBatchApiLock
         */
        if ($this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            return;
        }

        StoreFactory::createStore($this->connection)->configureSchema($schema);
    }
}
