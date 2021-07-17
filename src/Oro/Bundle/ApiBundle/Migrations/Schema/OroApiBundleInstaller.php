<?php

namespace Oro\Bundle\ApiBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Creates all database tables required for ApiBundle.
 */
class OroApiBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroApiAsyncOperationTable($schema);
        $this->createOroApiAsyncDataTable($schema);

        /** Foreign keys generation **/
        $this->addOroApiAsyncOperationForeignKeys($schema);
    }

    /**
     * Create oro_api_async_operation table
     */
    protected function createOroApiAsyncOperationTable(Schema $schema)
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
    protected function createOroApiAsyncDataTable(Schema $schema)
    {
        $table = $schema->createTable('oro_api_async_data');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('content', 'text');
        $table->addColumn('updated_at', 'integer');
        $table->addColumn('checksum', 'string', ['length' => 32]);
        $table->setPrimaryKey(['name']);
    }

    /**
     * Add oro_api_async_operation foreign keys.
     */
    protected function addOroApiAsyncOperationForeignKeys(Schema $schema)
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
