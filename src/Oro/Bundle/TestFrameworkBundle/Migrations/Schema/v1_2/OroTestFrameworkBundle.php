<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\WorkflowBundle\Migrations\Schema\RemoveWorkflowFieldsTrait;

class OroTestFrameworkBundle implements Migration
{
    use RemoveWorkflowFieldsTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createTestAuditDataTables($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function createTestAuditDataTables(Schema $schema)
    {
        $ownerTable = $schema->createTable('oro_test_dataaudit_owner');
        $ownerTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $ownerTable->addColumn('string_property', 'text', ['notnull' => false]);
        $ownerTable->addColumn('not_auditable_property', 'text', ['notnull' => false]);
        $ownerTable->addColumn('int_property', 'integer', ['notnull' => false]);
        $ownerTable->addColumn('serialized_property', 'text', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $ownerTable->addColumn('json_property', 'text', ['notnull' => false, 'comment' => '(DC2Type:json_array)']);
        $ownerTable->addColumn('date_property', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $ownerTable->addColumn('child_id', 'integer', ['notnull' => false]);
        $ownerTable->addUniqueIndex(['child_id'], 'UNIQ_B001FBEDD62C21B', []);
        $ownerTable->setPrimaryKey(['id']);

        $childTable = $schema->createTable('oro_test_dataaudit_child');
        $childTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $childTable->addColumn('string_property', 'text', ['notnull' => false]);
        $childTable->addColumn('owner_one_to_many_id', 'integer', ['notnull' => false]);
        $childTable->addColumn('not_auditable_property', 'text', ['notnull' => false]);
        $childTable->addIndex(['owner_one_to_many_id'], 'idx_test_dataaudit_child_owner_one_to_many_id', []);
        $childTable->setPrimaryKey(['id']);
        $childTable->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_owner'),
            ['owner_one_to_many_id'],
            ['id']
        );

        $ownerTable->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_child'),
            ['child_id'],
            ['id']
        );

        $childrenTable = $schema->createTable('oro_test_dataaudit_many2many');
        $childrenTable->addColumn('child_id', 'integer', ['notnull' => true]);
        $childrenTable->addColumn('owner_id', 'integer', ['notnull' => true]);
        $childrenTable->setPrimaryKey(['owner_id', 'child_id']);
        $childrenTable->addIndex(['owner_id'], 'FK_B67A508B7E3C61F9');
        $childrenTable->addUniqueIndex(['child_id'], 'UNIQ_B67A508BDD62C21B');
        $childrenTable->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_owner'),
            ['owner_id'],
            ['id']
        );
        $childrenTable->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_child'),
            ['child_id'],
            ['id'],
            ['unique' => true]
        );
    }
}
