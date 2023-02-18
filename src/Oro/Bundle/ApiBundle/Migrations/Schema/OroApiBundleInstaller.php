<?php

namespace Oro\Bundle\ApiBundle\Migrations\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\Lock\Store\StoreFactory;

/**
 * Creates all database tables required for ApiBundle.
 */
class OroApiBundleInstaller implements Installation, ConnectionAwareInterface
{
    private Connection $connection;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_1';
    }

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
        /** Tables generation **/
        $this->createOroApiAsyncOperationTable($schema);
        $this->createOroApiAsyncDataTable($schema);
        $this->createBatchApiLockTable($schema);

        /** Foreign keys generation **/
        $this->addOroApiAsyncOperationForeignKeys($schema);
    }

    /**
     * Create oro_api_async_operation table
     */
    private function createOroApiAsyncOperationTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_api_async_operation');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('status', 'string', ['length' => 10]);
        $table->addColumn('progress', 'percent', ['comment' => '(DC2Type:percent)', 'notnull' => false]);
        $table->addColumn('job_id', 'integer', ['notnull' => false]);
        $table->addColumn('data_file_name', 'string', ['length' => 50]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('action_name', 'string', ['length' => 20]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('elapsed_time', 'integer');
        $table->addColumn('has_errors', 'boolean', ['default' => false]);
        $table->addColumn('summary', 'json_array', ['comment' => '(DC2Type:json_array)', 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_owner_id'], 'IDX_F4BCF3999EB185F9', []);
        $table->addIndex(['organization_id'], 'IDX_F4BCF39932C8A3DE', []);
    }

    /**
     * Create oro_api_async_data table
     */
    private function createOroApiAsyncDataTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_api_async_data');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('content', 'text');
        $table->addColumn('updated_at', 'integer');
        $table->addColumn('checksum', 'string', ['length' => 32]);
        $table->setPrimaryKey(['name']);
    }

    /**
     * Create table for Batch API locks.
     */
    private function createBatchApiLockTable(Schema $schema): void
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

    /**
     * Add oro_api_async_operation foreign keys.
     */
    private function addOroApiAsyncOperationForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_api_async_operation');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
