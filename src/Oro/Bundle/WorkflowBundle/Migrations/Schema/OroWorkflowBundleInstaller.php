<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroWorkflowBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v2_6';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
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
        $this->createOroWorkflowRestrictionTable($schema);
        $this->createOroWorkflowRestrictionIdentityTable($schema);
        $this->createOroWorkflowStepTable($schema);
        $this->createOroWorkflowTransTriggerTable($schema);
        $this->createOroWorkflowScopesTable($schema);

        /** Foreign keys generation **/
        $this->addOroWorkflowItemForeignKeys($schema);
        $this->addOroWorkflowEntityAclForeignKeys($schema);
        $this->addOroWorkflowTransitionLogForeignKeys($schema);
        $this->addOroProcessJobForeignKeys($schema);
        $this->addOroProcessTriggerForeignKeys($schema);
        $this->addOroWorkflowEntityAclIdentForeignKeys($schema);
        $this->addOroWorkflowDefinitionForeignKeys($schema);
        $this->addOroWorkflowRestrictionForeignKeys($schema);
        $this->addOroWorkflowRestrictionIdentityForeignKeys($schema);
        $this->addOroWorkflowStepForeignKeys($schema);
        $this->addOroWorkflowTransTriggerForeignKeys($schema);
        $this->addOroWorkflowScopesForeignKeys($schema);

        $this->addWorkflowFieldsToEmailNotificationTable($schema);
    }

    /**
     * Create oro_workflow_item table
     */
    private function createOroWorkflowItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_workflow_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('current_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_name', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('entity_class', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('created', 'datetime');
        $table->addColumn('updated', 'datetime', ['notnull' => false]);
        $table->addColumn('data', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['workflow_name'], 'idx_169789ae1bbc6e3d');
        $table->addIndex(['entity_class', 'entity_id'], 'oro_workflow_item_entity_idx');
        $table->addIndex(['current_step_id'], 'idx_169789aed9bf9b19');
        $table->addUniqueIndex(['entity_id', 'workflow_name'], 'oro_workflow_item_entity_definition_unq');
    }

    /**
     * Create oro_workflow_entity_acl table
     */
    private function createOroWorkflowEntityAclTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_workflow_entity_acl');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('workflow_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('attribute', 'string', ['length' => 255]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('updatable', 'boolean');
        $table->addColumn('deletable', 'boolean');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['workflow_name'], 'idx_c54c5e5b1bbc6e3d');
        $table->addIndex(['workflow_step_id'], 'idx_c54c5e5b71fe882c');
        $table->addUniqueIndex(['workflow_name', 'attribute', 'workflow_step_id'], 'oro_workflow_acl_unique_idx');
    }

    /**
     * Create oro_workflow_transition_log table
     */
    private function createOroWorkflowTransitionLogTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_workflow_transition_log');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('step_from_id', 'integer', ['notnull' => false]);
        $table->addColumn('step_to_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('transition', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('transition_date', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['step_to_id'], 'idx_b3d57b302c17bd9a');
        $table->addIndex(['step_from_id'], 'idx_b3d57b30c8335fe4');
        $table->addIndex(['workflow_item_id'], 'idx_b3d57b301023c4ee');
    }

    /**
     * Create oro_process_job table
     */
    private function createOroProcessJobTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_process_job');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('process_trigger_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_hash', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('serialized_data', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_hash'], 'process_job_entity_hash_idx');
        $table->addIndex(['process_trigger_id'], 'idx_1957630f196ffde');
    }

    /**
     * Create oro_process_trigger table
     */
    private function createOroProcessTriggerTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_process_trigger');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('definition_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('event', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('field', 'string', ['notnull' => false, 'length' => 150]);
        $table->addColumn('queued', 'boolean');
        $table->addColumn('time_shift', 'integer', ['notnull' => false]);
        $table->addColumn('cron', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('priority', 'smallint');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['definition_name'], 'idx_48b327bccb9d81d2');
        $table->addUniqueIndex(['event', 'field', 'definition_name', 'cron'], 'process_trigger_unique_idx');
    }

    /**
     * Create oro_workflow_entity_acl_ident table
     */
    private function createOroWorkflowEntityAclIdentTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_workflow_entity_acl_ident');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_entity_acl_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_id', 'entity_class'], 'oro_workflow_entity_acl_identity_idx');
        $table->addUniqueIndex(
            ['workflow_entity_acl_id', 'entity_id', 'workflow_item_id'],
            'oro_workflow_entity_acl_identity_unique_idx'
        );
        $table->addIndex(['workflow_item_id'], 'idx_367002f11023c4ee');
        $table->addIndex(['workflow_entity_acl_id'], 'idx_367002f1160f68fb');
    }

    /**
     * Create oro_workflow_definition table
     */
    private function createOroWorkflowDefinitionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_workflow_definition');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('start_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('related_entity', 'string', ['length' => 255]);
        $table->addColumn('entity_attribute_name', 'string', ['length' => 255]);
        $table->addColumn('steps_display_ordered', 'boolean');
        $table->addColumn('system', 'boolean');
        $table->addColumn('active', 'boolean', ['default' => false]);
        $table->addColumn('priority', 'integer', ['default' => 0]);
        $table->addColumn(
            'configuration',
            'json',
            [
                'comment' => '(DC2Type:json)',
                'customSchemaOptions' => ['jsonb' => true]
            ]
        );
        $table->addColumn(
            'metadata',
            'json',
            [
                'comment' => '(DC2Type:json)',
                'customSchemaOptions' => ['jsonb' => true]
            ]
        );
        $table->addColumn('exclusive_active_groups', 'simple_array', [
            'notnull' => false,
        ]);
        $table->addColumn('exclusive_record_groups', 'simple_array', [
            'notnull' => false,
        ]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('applications', 'simple_array', ['notnull' => true]);
        $table->setPrimaryKey(['name']);
        $table->addIndex(['start_step_id'], 'idx_6f737c368377424f');
    }

    /**
     * Create oro_process_definition table
     */
    private function createOroProcessDefinitionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_process_definition');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('enabled', 'boolean');
        $table->addColumn('related_entity', 'string', ['length' => 255]);
        $table->addColumn('execution_order', 'smallint');
        $table->addColumn('exclude_definitions', 'simple_array', [
            'notnull' => false
        ]);
        $table->addColumn(
            'actions_configuration',
            'json',
            [
                'comment' => '(DC2Type:json)',
                'customSchemaOptions' => ['jsonb' => true]
            ]
        );
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn(
            'pre_conditions_configuration',
            'json',
            [
                'comment' => '(DC2Type:json)',
                'notnull' => false,
                'customSchemaOptions' => ['jsonb' => true]
            ]
        );
        $table->setPrimaryKey(['name']);
    }

    /**
     * Create oro_workflow_restriction table
     */
    private function createOroWorkflowRestrictionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_workflow_restriction');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_name', 'string', ['length' => 255]);
        $table->addColumn('workflow_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('attribute', 'string', ['length' => 255]);
        $table->addColumn('field', 'string', ['length' => 150]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('mode', 'string', ['length' => 8]);
        $table->addColumn(
            'mode_values',
            'json',
            [
                'notnull' => false,
                'comment' => '(DC2Type:json)',
                'customSchemaOptions' => ['jsonb' => true]
            ]
        );
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            ['workflow_name', 'workflow_step_id', 'field', 'entity_class', 'mode'],
            'oro_workflow_restriction_idx'
        );
    }

    /**
     * Create oro_workflow_restriction_ident table
     */
    private function createOroWorkflowRestrictionIdentityTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_workflow_restriction_ident');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_restriction_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_item_id', 'integer');
        $table->addColumn('entity_id', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_id'], 'oro_workflow_restr_ident_idx');
        $table->addUniqueIndex(
            ['workflow_restriction_id', 'entity_id', 'workflow_item_id'],
            'oro_workflow_restr_ident_unique_idx'
        );
    }

    /**
     * Create oro_workflow_step table
     */
    private function createOroWorkflowStepTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_workflow_step');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('step_order', 'integer');
        $table->addColumn('is_final', 'boolean');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['name'], 'oro_workflow_step_name_idx');
        $table->addIndex(['workflow_name'], 'idx_4a35528c1bbc6e3d');
        $table->addUniqueIndex(['workflow_name', 'name'], 'oro_workflow_step_unique_idx');
    }

    /**
     * Create oro_workflow_trans_trigger table
     */
    private function createOroWorkflowTransTriggerTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_workflow_trans_trigger');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_name', 'string', ['length' => 255]);
        $table->addColumn('entity_class', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('queued', 'boolean');
        $table->addColumn('transition_name', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('cron', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('filter', 'text', ['notnull' => false]);
        $table->addColumn('event', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('field', 'string', ['notnull' => false, 'length' => 150]);
        $table->addColumn('require', 'text', ['notnull' => false]);
        $table->addColumn('relation', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_workflow_scopes table
     */
    private function createOroWorkflowScopesTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_workflow_scopes');
        $table->addColumn('workflow_name', 'string', ['length' => 255]);
        $table->addColumn('scope_id', 'integer');
        $table->setPrimaryKey(['workflow_name', 'scope_id']);
    }

    /**
     * Add oro_workflow_item foreign keys.
     */
    private function addOroWorkflowItemForeignKeys(Schema $schema): void
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
     */
    private function addOroWorkflowEntityAclForeignKeys(Schema $schema): void
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
     */
    private function addOroWorkflowTransitionLogForeignKeys(Schema $schema): void
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
     */
    private function addOroProcessJobForeignKeys(Schema $schema): void
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
     */
    private function addOroProcessTriggerForeignKeys(Schema $schema): void
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
     */
    private function addOroWorkflowEntityAclIdentForeignKeys(Schema $schema): void
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
     */
    private function addOroWorkflowDefinitionForeignKeys(Schema $schema): void
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
     * Add oro_workflow_restriction foreign keys.
     */
    private function addOroWorkflowRestrictionForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_workflow_restriction');
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

    /**
     * Add oro_workflow_restriction_ident foreign keys.
     */
    private function addOroWorkflowRestrictionIdentityForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_workflow_restriction_ident');
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

    /**
     * Add oro_workflow_step foreign keys.
     */
    private function addOroWorkflowStepForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_workflow_step');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_definition'),
            ['workflow_name'],
            ['name'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_workflow_trans_trigger foreign keys.
     */
    private function addOroWorkflowTransTriggerForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_workflow_trans_trigger');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_definition'),
            ['workflow_name'],
            ['name'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_workflow_scopes foreign keys.
     */
    private function addOroWorkflowScopesForeignKeys(Schema $schema): void
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

    private function addWorkflowFieldsToEmailNotificationTable(Schema $schema): void
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'oro_notification_email_notif',
            'workflow_definition',
            'oro_workflow_definition',
            'name',
            [
                'entity' => ['label' => 'oro.workflow.workflowdefinition.entity_label'],
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'is_extend' => true,
                    'nullable' => true
                ],
                'datagrid' => [
                    'is_visible' => DatagridScope::IS_VISIBLE_TRUE,
                    'show_filter' => true,
                    'order' => 30
                ],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => false],
                'dataaudit' => ['auditable' => false]
            ]
        );

        $table = $schema->getTable('oro_notification_email_notif');
        $table->addColumn(
            'workflow_transition_name',
            'string',
            [
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'entity' => ['label' => 'oro.workflow.workflowdefinition.transition_name.label'],
                    'extend' => [
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'is_extend' => true,
                        'length' => 255
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_TRUE,
                        'show_filter' => true,
                        'order' => 40
                    ],
                    'form' => ['is_enabled' => false],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                    'dataaudit' => ['auditable' => false]
                ],
            ]
        );
    }
}
