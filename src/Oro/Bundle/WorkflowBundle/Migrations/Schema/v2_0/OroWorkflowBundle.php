<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWorkflowBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroWorkflowScopesTable($schema);

        /** Foreign keys generation **/
        $this->addOroWorkflowScopesForeignKeys($schema);
    }

    /**
     * Create oro_workflow_scopes table
     *
     * @param Schema $schema
     */
    protected function createOroWorkflowScopesTable(Schema $schema)
    {
        $table = $schema->createTable('oro_workflow_scopes');
        $table->addColumn('workflow_name', 'string', ['length' => 255]);
        $table->addColumn('scope_id', 'integer', []);
        $table->setPrimaryKey(['workflow_name', 'scope_id']);
    }

    /**
     * Add oro_workflow_scopes foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWorkflowScopesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_workflow_scopes');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
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
