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
     * @param Schema $schema
     */
    public static function createOroWorkflowEntityRestrictionsTable(Schema $schema)
    {
        $table = $schema->createTable('oro_workflow_restriction');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_name', 'string', ['length' => 255]);
        $table->addColumn('workflow_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('attribute', 'string', ['length' => 255]);
        $table->addColumn('field', 'string', ['length' => 255]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('mode', 'string', ['length' => 255]);
        $table->addColumn('mode_values', 'json_array', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            ['workflow_name', 'workflow_step_id', 'field', 'entity_class', 'mode'],
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

        $table = $schema->createTable('oro_workflow_restriction_ident');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_restriction_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_item_id', 'integer');
        $table->addColumn('entity_id', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_id'], 'oro_workflow_restr_ident_idx', []);
        $table->addUniqueIndex(
            ['workflow_restriction_id', 'entity_id', 'workflow_item_id'],
            'oro_workflow_restr_ident_unique_idx'
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_restriction'),
            ['workflow_restriction_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_item'),
            ['workflow_item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
