<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateEntityRestrictionsTable implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        self::createOroWorkflowEntityRestrictionsTable($schema);
    }

    /**
     * Create oro_workflow_entity_acl table
     *
     * @param Schema $schema
     */
    public static function createOroWorkflowEntityRestrictionsTable(Schema $schema)
    {
        $table = $schema->createTable('oro_workflow_restriction');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_name', 'string', ['length' => 255]);
        $table->addColumn('workflow_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('field', 'string', ['length' => 255]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('mode', 'string', ['length' => 255]);
        $table->addColumn('mode_values', 'json_array', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            ['workflow_name', 'workflow_step_id', 'attribute', 'entity_class', 'mode'],
            'oro_workflow_restriction_idx'
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['workflow_step_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_definition'),
            ['workflow_name'],
            ['name'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
