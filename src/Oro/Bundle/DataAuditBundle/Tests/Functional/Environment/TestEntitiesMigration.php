<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Environment;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class TestEntitiesMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $testDataAuditOwnerTableCreated = $this->createTestDataAuditOwnerTable($schema);
        $testDataAuditChildTableCreated = $this->createTestDataAuditChildTable($schema);
        $testDataAuditManyToManyTableCreated = $this->createTestDataAuditManyToManyTable($schema);

        /** Foreign keys generation **/
        if ($testDataAuditOwnerTableCreated) {
            $this->addTestDataAuditOwnerForeignKeys($schema);
        }
        if ($testDataAuditChildTableCreated) {
            $this->addTestDataAuditChildForeignKeys($schema);
        }
        if ($testDataAuditManyToManyTableCreated) {
            $this->addTestDataAuditManyToManyForeignKeys($schema);
        }
    }

    /**
     * Create oro_test_dataaudit_owner table
     *
     * @param Schema $schema
     *
     * @return bool
     */
    protected function createTestDataAuditOwnerTable(Schema $schema)
    {
        if ($schema->hasTable('oro_test_dataaudit_owner')) {
            return false;
        }

        $table = $schema->createTable('oro_test_dataaudit_owner');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('string_property', 'text', ['notnull' => false]);
        $table->addColumn('not_auditable_property', 'text', ['notnull' => false]);
        $table->addColumn('int_property', 'integer', ['notnull' => false]);
        $table->addColumn('serialized_property', 'text', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $table->addColumn('json_property', 'text', ['notnull' => false, 'comment' => '(DC2Type:json_array)']);
        $table->addColumn('date_property', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('child_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['child_id'], 'UNIQ_B001FBEDD62C21B', []);
        $table->setPrimaryKey(['id']);

        return true;
    }

    /**
     * Create oro_test_dataaudit_child table
     *
     * @param Schema $schema
     *
     * @return bool
     */
    protected function createTestDataAuditChildTable(Schema $schema)
    {
        if ($schema->hasTable('oro_test_dataaudit_child')) {
            return false;
        }

        $table = $schema->createTable('oro_test_dataaudit_child');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('string_property', 'text', ['notnull' => false]);
        $table->addColumn('owner_one_to_many_id', 'integer', ['notnull' => false]);
        $table->addColumn('not_auditable_property', 'text', ['notnull' => false]);
        $table->addIndex(['owner_one_to_many_id'], 'idx_test_dataaudit_child_owner_one_to_many_id', []);
        $table->setPrimaryKey(['id']);

        return true;
    }

    /**
     * Create oro_test_dataaudit_many2many table
     *
     * @param Schema $schema
     *
     * @return bool
     */
    protected function createTestDataAuditManyToManyTable(Schema $schema)
    {
        if ($schema->hasTable('oro_test_dataaudit_many2many')) {
            return false;
        }

        $table = $schema->createTable('oro_test_dataaudit_many2many');
        $table->addColumn('child_id', 'integer', ['notnull' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['owner_id', 'child_id']);
        $table->addIndex(['owner_id'], 'FK_B67A508B7E3C61F9');
        $table->addUniqueIndex(['child_id'], 'UNIQ_B67A508BDD62C21B');

        return true;
    }

    /**
     * Add oro_test_dataaudit_owner foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestDataAuditOwnerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_test_dataaudit_owner');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_child'),
            ['child_id'],
            ['id']
        );
    }

    /**
     * Add oro_test_dataaudit_child foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestDataAuditChildForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_test_dataaudit_child');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_owner'),
            ['owner_one_to_many_id'],
            ['id']
        );
    }

    /**
     * Add oro_test_dataaudit_many2many foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestDataAuditManyToManyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_test_dataaudit_many2many');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_owner'),
            ['owner_id'],
            ['id']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_child'),
            ['child_id'],
            ['id'],
            ['unique' => true]
        );
    }
}
