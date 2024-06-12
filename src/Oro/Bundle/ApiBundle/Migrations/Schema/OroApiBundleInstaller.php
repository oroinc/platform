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
        return 'v1_3';
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
        $this->createAsyncOperationTable($schema);
        $this->createAsyncDataTable($schema);
        $this->createBatchApiLockTable($schema);
        $this->createOpenApiSpecificationTable($schema);

        /** Foreign keys generation **/
        $this->addAsyncOperationForeignKeys($schema);
        $this->addOpenApiSpecificationForeignKeys($schema);
    }

    /**
     * Create oro_api_async_operation table
     */
    private function createAsyncOperationTable(Schema $schema): void
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
    private function createAsyncDataTable(Schema $schema): void
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
     * Create oro_api_openapi_specification table
     */
    private function createOpenApiSpecificationTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_api_openapi_specification');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('status', 'string', ['length' => 8]);
        $table->addColumn('published', 'boolean');
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('public_slug', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('view', 'string', ['length' => 100]);
        $table->addColumn('format', 'string', ['length' => 20]);
        $table->addColumn('entities', 'simple_array', ['comment' => '(DC2Type:simple_array)', 'notnull' => false]);
        $table->addColumn('server_urls', 'simple_array', ['comment' => '(DC2Type:simple_array)', 'notnull' => false]);
        $table->addColumn('specification', 'text', ['notnull' => false]);
        $table->addColumn('specification_created_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'IDX_9AE6DA3A32C8A3DE');
        $table->addIndex(['user_owner_id'], 'IDX_9AE6DA3A9EB185F9');
    }

    /**
     * Add oro_api_async_operation foreign keys.
     */
    private function addAsyncOperationForeignKeys(Schema $schema): void
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

    /**
     * Add oro_api_openapi_specification foreign keys.
     */
    private function addOpenApiSpecificationForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_api_openapi_specification');
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
