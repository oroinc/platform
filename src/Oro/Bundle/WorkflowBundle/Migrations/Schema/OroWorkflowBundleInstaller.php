<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_13\CreateEntityRestrictionsTable;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroWorkflowBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_13';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroWorkflowItemTable($schema);
        $this->createOroWorkflowEntityAclTable($schema);
        $this->createOroWorkflowTransitionLogTable($schema);
        $this->createOroProcessJobTable($schema);
        $this->createOroProcessTriggerTable($schema);
        $this->createOroWorkflowEntityAclIdentTable($schema);
        $this->createOroWorkflowDefinitionTable($schema);
        $this->createOroProcessDefinitionTable($schema);
        $this->createOroWorkflowStepTable($schema);

        /** Foreign keys generation **/
        $this->addOroWorkflowItemForeignKeys($schema);
        $this->addOroWorkflowEntityAclForeignKeys($schema);
        $this->addOroWorkflowTransitionLogForeignKeys($schema);
        $this->addOroProcessJobForeignKeys($schema);
        $this->addOroProcessTriggerForeignKeys($schema);
        $this->addOroWorkflowEntityAclIdentForeignKeys($schema);
        $this->addOroWorkflowDefinitionForeignKeys($schema);
        $this->addOroWorkflowStepForeignKeys($schema);
        
        CreateEntityRestrictionsTable::createOroWorkflowEntityRestrictionsTable($schema);
    }

    /**
     * Create oro_workflow_item table
     *
     * @param Schema $schema
     */
    protected function createOroWorkflowItemTable(Schema $schema)
    {
        $table = $schema->createTable('oro_workflow_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('current_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_name', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_class', 'string', ['notnull' => false]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', ['notnull' => false]);
        $table->addColumn('data', 'text', ['notnull' => false]);
        $table->addIndex(['workflow_name'], 'idx_169789ae1bbc6e3d', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['current_step_id'], 'idx_169789aed9bf9b19', []);
        $table->addUniqueIndex(['entity_id', 'workflow_name'], 'oro_workflow_item_entity_definition_unq');
    }

    /**
     * Create oro_workflow_entity_acl table
     *
     * @param Schema $schema
     */
    protected function createOroWorkflowEntityAclTable(Schema $schema)
    {
        $table = $schema->createTable('oro_workflow_entity_acl');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('workflow_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('attribute', 'string', ['length' => 255]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('updatable', 'boolean', []);
        $table->addColumn('deletable', 'boolean', []);
        $table->addIndex(['workflow_name'], 'idx_c54c5e5b1bbc6e3d', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['workflow_step_id'], 'idx_c54c5e5b71fe882c', []);
        $table->addUniqueIndex(['workflow_name', 'attribute', 'workflow_step_id'], 'oro_workflow_acl_unique_idx');
    }

    /**
     * Create oro_workflow_transition_log table
     *
     * @param Schema $schema
     */
    protected function createOroWorkflowTransitionLogTable(Schema $schema)
    {
        $table = $schema->createTable('oro_workflow_transition_log');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('step_from_id', 'integer', ['notnull' => false]);
        $table->addColumn('step_to_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('transition', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('transition_date', 'datetime', []);
        $table->addIndex(['step_to_id'], 'idx_b3d57b302c17bd9a', []);
        $table->addIndex(['step_from_id'], 'idx_b3d57b30c8335fe4', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['workflow_item_id'], 'idx_b3d57b301023c4ee', []);
    }

    /**
     * Create oro_process_job table
     *
     * @param Schema $schema
     */
    protected function createOroProcessJobTable(Schema $schema)
    {
        $table = $schema->createTable('oro_process_job');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('process_trigger_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_hash', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('serialized_data', 'text', ['notnull' => false]);
        $table->addIndex(['entity_hash'], 'process_job_entity_hash_idx', []);
        $table->addIndex(['process_trigger_id'], 'idx_1957630f196ffde', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_process_trigger table
     *
     * @param Schema $schema
     */
    protected function createOroProcessTriggerTable(Schema $schema)
    {
        $table = $schema->createTable('oro_process_trigger');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('definition_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('event', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('field', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('queued', 'boolean', []);
        $table->addColumn('time_shift', 'integer', ['notnull' => false]);
        $table->addColumn('cron', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('priority', 'smallint', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['definition_name'], 'idx_48b327bccb9d81d2', []);
        $table->addUniqueIndex(['event', 'field', 'definition_name', 'cron'], 'process_trigger_unique_idx');
    }

    /**
     * Create oro_workflow_entity_acl_ident table
     *
     * @param Schema $schema
     */
    protected function createOroWorkflowEntityAclIdentTable(Schema $schema)
    {
        $table = $schema->createTable('oro_workflow_entity_acl_ident');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_entity_acl_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_id', 'entity_class'], 'oro_workflow_entity_acl_identity_idx', []);
        $table->addUniqueIndex(
            ['workflow_entity_acl_id', 'entity_id', 'workflow_item_id'],
            'oro_workflow_entity_acl_identity_unique_idx'
        );
        $table->addIndex(['workflow_item_id'], 'idx_367002f11023c4ee', []);
        $table->addIndex(['workflow_entity_acl_id'], 'idx_367002f1160f68fb', []);
    }

    /**
     * Create oro_workflow_definition table
     *
     * @param Schema $schema
     */
    protected function createOroWorkflowDefinitionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_workflow_definition');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('start_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('related_entity', 'string', ['length' => 255]);
        $table->addColumn('entity_attribute_name', 'string', ['length' => 255]);
        $table->addColumn('steps_display_ordered', 'boolean', []);
        $table->addColumn('system', 'boolean', []);
        $table->addColumn('configuration', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addIndex(['start_step_id'], 'idx_6f737c368377424f', []);
        $table->setPrimaryKey(['name']);
    }

    /**
     * Create oro_process_definition table
     *
     * @param Schema $schema
     */
    protected function createOroProcessDefinitionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_process_definition');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('enabled', 'boolean', []);
        $table->addColumn('related_entity', 'string', ['length' => 255]);
        $table->addColumn('execution_order', 'smallint', []);
        $table->addColumn(
            'exclude_definitions',
            'simple_array',
            ['notnull' => false, 'comment' => '(DC2Type:simple_array)']
        );
        $table->addColumn('actions_configuration', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn(
            'pre_conditions_configuration',
            'array',
            ['notnull' => false, 'comment' => '(DC2Type:array)']
        );
        $table->setPrimaryKey(['name']);
    }

    /**
     * Create oro_workflow_step table
     *
     * @param Schema $schema
     */
    protected function createOroWorkflowStepTable(Schema $schema)
    {
        $table = $schema->createTable('oro_workflow_step');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('step_order', 'integer', []);
        $table->addColumn('is_final', 'boolean', []);
        $table->addIndex(['name'], 'oro_workflow_step_name_idx', []);
        $table->addIndex(['workflow_name'], 'idx_4a35528c1bbc6e3d', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['workflow_name', 'name'], 'oro_workflow_step_unique_idx');
    }

    /**
     * Add oro_workflow_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWorkflowItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_workflow_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['current_step_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_definition'),
            ['workflow_name'],
            ['name'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_workflow_entity_acl foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWorkflowEntityAclForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_workflow_entity_acl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_definition'),
            ['workflow_name'],
            ['name'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['workflow_step_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_workflow_transition_log foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWorkflowTransitionLogForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_workflow_transition_log');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['step_from_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['step_to_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_item'),
            ['workflow_item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_process_job foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroProcessJobForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_process_job');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_process_trigger'),
            ['process_trigger_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_process_trigger foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroProcessTriggerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_process_trigger');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_process_definition'),
            ['definition_name'],
            ['name'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_workflow_entity_acl_ident foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWorkflowEntityAclIdentForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_workflow_entity_acl_ident');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_entity_acl'),
            ['workflow_entity_acl_id'],
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

    /**
     * Add oro_workflow_definition foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWorkflowDefinitionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_workflow_definition');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['start_step_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_workflow_step foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWorkflowStepForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_workflow_step');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_definition'),
            ['workflow_name'],
            ['name'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
