<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWorkflowBundle implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroWorkflowTransTriggerTable($schema);
        $this->addOroWorkflowTransTriggerForeignKeys($schema);
    }

    /**
     * Create oro_workflow_trans_trigger table
     *
     * @param Schema $schema
     */
    protected function createOroWorkflowTransTriggerTable(Schema $schema)
    {
        $table = $schema->createTable('oro_workflow_trans_trigger');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_name', 'string', ['length' => 255]);
        $table->addColumn('entity_class', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('queued', 'boolean', []);
        $table->addColumn('transition_name', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('cron', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('filter', 'text', ['notnull' => false]);
        $table->addColumn('event', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('field', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('require', 'text', ['notnull' => false]);
        $table->addColumn('relation', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }


    /**
     * Add oro_workflow_trans_trigger foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWorkflowTransTriggerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_workflow_trans_trigger');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_definition'),
            ['workflow_name'],
            ['name'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
