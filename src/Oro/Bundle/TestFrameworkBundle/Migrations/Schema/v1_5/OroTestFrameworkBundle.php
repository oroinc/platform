<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTestFrameworkBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createTestUserOwnershipTable($schema);
        $this->addTestUserOwnershipForeignKeys($schema);
    }

    /**
     * Create test_user_ownership table
     *
     * @param Schema $schema
     */
    protected function createTestUserOwnershipTable(Schema $schema)
    {
        $table = $schema->createTable('test_user_ownership');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'IDX_673C997D32C8A3DE', []);
        $table->addIndex(['owner_id'], 'IDX_673C997D7E3C61F9', []);
    }

    /**
     * Add test_user_ownership foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestUserOwnershipForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_user_ownership');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
