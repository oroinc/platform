<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Bundle\WorkflowBundle\Provider\WorkflowVirtualRelationProvider;

class OroWorkflowBundle implements Migration
{
    use ContainerAwareTrait;

    const OLD_ITEMS_RELATION = 'workflowItem';
    const OLD_STEPS_RELATION = 'workflowStep';
    const NEW_ITEMS_RELATION = WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME;
    const NEW_STEPS_RELATION = WorkflowVirtualRelationProvider::STEPS_RELATION_NAME;
    
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroWorkflowGroupTable($schema);
        $this->createOroWorkflowDefToGroupTable($schema);

        /** Foreign keys generation **/
        $this->addOroWorkflowDefToGroupForeignKeys($schema);

        $this->updateWorkflowDefinitionFields($schema);
        $this->updateReportsDefinitions($queries);
        $queries->addQuery(new RemoveExtendedFieldsQuery());
        $queries->addPostQuery(new MoveActiveFromConfigToFieldQuery());
    }

    /**
     * Create oro_workflow_group table
     *
     * @param Schema $schema
     */
    protected function createOroWorkflowGroupTable(Schema $schema)
    {
        $table = $schema->createTable('oro_workflow_group');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type', 'smallint', []);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['type', 'name'], 'oro_workflow_group_unique_idx');
    }

    /**
     * Create oro_workflow_def_to_group table
     *
     * @param Schema $schema
     */
    protected function createOroWorkflowDefToGroupTable(Schema $schema)
    {
        $table = $schema->createTable('oro_workflow_def_to_group');
        $table->addColumn('workflow_definition_name', 'string', ['length' => 255]);
        $table->addColumn('workflow_group_id', 'integer', []);
        $table->addIndex(['workflow_group_id'], 'idx_315122013537265d', []);
        $table->setPrimaryKey(['workflow_definition_name', 'workflow_group_id']);
        $table->addIndex(['workflow_definition_name'], 'idx_3151220193298d04', []);
    }

    /**
     * Add oro_workflow_def_to_group foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWorkflowDefToGroupForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_workflow_def_to_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_group'),
            ['workflow_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_definition'),
            ['workflow_definition_name'],
            ['name'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param QueryBag $queries
     */
    protected function updateReportsDefinitions(QueryBag $queries)
    {
        $queries->addPostQuery(
            sprintf(
                'UPDATE oro_report SET definition = REPLACE(definition, \'%s\', \'%s\')',
                self::OLD_ITEMS_RELATION,
                self::NEW_ITEMS_RELATION
            )
        );
        $queries->addPostQuery(
            sprintf(
                'UPDATE oro_report SET definition = REPLACE(definition, \'%s\', \'%s\')',
                self::OLD_STEPS_RELATION,
                self::NEW_STEPS_RELATION
            )
        );
    }

    /**
     * @param Schema $schema
     */
    private function updateWorkflowDefinitionFields(Schema $schema)
    {
        $table = $schema->getTable('oro_workflow_definition');
        $table->addColumn('active', 'boolean');
        $table->addColumn('priority', 'integer');
    }
}
