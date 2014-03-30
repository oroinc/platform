<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWorkflowBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::oroWorkflowDefinitionTable($schema);
        self::oroWorkflowItemTable($schema);
        self::oroWorkflowStepTable($schema);
        self::oroWorkflowTransitionLogTable($schema);
        self::oroWorkflowEntityAclTable($schema);
        self::oroWorkflowEntityAclIdentityTable($schema);

        self::oroWorkflowDefinitionForeignKeys($schema);
        self::oroWorkflowItemForeignKeys($schema);
        self::oroWorkflowStepForeignKeys($schema);
        self::oroWorkflowTransitionLogForeignKeys($schema);
        self::oroWorkflowEntityAclForeignKeys($schema);
        self::oroWorkflowEntityAclIdentityForeignKeys($schema);
    }

    /**
     * Generate table oro_workflow_definition
     *
     * @param Schema $schema
     */
    public static function oroWorkflowDefinitionTable(Schema $schema)
    {
        /** Generate table oro_workflow_definition **/
        $table = $schema->createTable('oro_workflow_definition');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('start_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('related_entity', 'string', ['length' => 255]);
        $table->addColumn('entity_attribute_name', 'string', ['length' => 255]);
        $table->addColumn('steps_display_ordered', 'boolean', []);
        $table->addColumn('enabled', 'boolean', []);
        $table->addColumn('configuration', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['name']);
        $table->addIndex(['start_step_id'], 'IDX_6F737C368377424F', []);
        $table->addIndex(['enabled'], 'oro_workflow_definition_enabled_idx', []);
        /** End of generate table oro_workflow_definition **/
    }

    /**
     * Generate table oro_workflow_item
     *
     * @param Schema $schema
     */
    public static function oroWorkflowItemTable(Schema $schema)
    {
        /** Generate table oro_workflow_item **/
        $table = $schema->createTable('oro_workflow_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_name', 'string', ['length' => 255]);
        $table->addColumn('current_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('closed', 'boolean', []);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', ['notnull' => false]);
        $table->addColumn('data', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['entity_id', 'workflow_name'], 'oro_workflow_item_entity_definition_unq');
        $table->addIndex(['current_step_id'], 'IDX_169789AED9BF9B19', []);
        $table->addIndex(['workflow_name'], 'IDX_169789AE1BBC6E3D', []);
        /** End of generate table oro_workflow_item **/
    }

    /**
     * Generate table oro_workflow_step
     *
     * @param Schema $schema
     */
    public static function oroWorkflowStepTable(Schema $schema)
    {
        /** Generate table oro_workflow_step **/
        $table = $schema->createTable('oro_workflow_step');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('step_order', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['workflow_name', 'name'], 'oro_workflow_step_unique_idx');
        $table->addIndex(['workflow_name'], 'IDX_4A35528C1BBC6E3D', []);
        /** End of generate table oro_workflow_step **/
    }

    /**
     * Generate table oro_workflow_transition_log
     *
     * @param Schema $schema
     */
    public static function oroWorkflowTransitionLogTable(Schema $schema)
    {
        /** Generate table oro_workflow_transition_log **/
        $table = $schema->createTable('oro_workflow_transition_log');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('step_to_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('step_from_id', 'integer', ['notnull' => false]);
        $table->addColumn('transition', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('transition_date', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['workflow_item_id'], 'IDX_B3D57B301023C4EE', []);
        $table->addIndex(['step_from_id'], 'IDX_B3D57B30C8335FE4', []);
        $table->addIndex(['step_to_id'], 'IDX_B3D57B302C17BD9A', []);
        /** End of generate table oro_workflow_transition_log **/
    }

    /**
     * Generate table oro_workflow_entity_acl
     *
     * @param Schema $schema
     */
    public static function oroWorkflowEntityAclTable(Schema $schema)
    {
        /** Generate table oro_workflow_entity_acl **/
        $table = $schema->createTable('oro_workflow_entity_acl');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('attribute', 'string', ['length' => 255]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('updatable', 'boolean', []);
        $table->addColumn('deletable', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['workflow_name', 'attribute', 'workflow_step_id'], 'oro_workflow_acl_unique_idx');
        $table->addIndex(['workflow_name'], 'IDX_C54C5E5B1BBC6E3D', []);
        $table->addIndex(['workflow_step_id'], 'IDX_C54C5E5B71FE882C', []);
        /** End of generate table oro_workflow_entity_acl **/
    }

    /**
     * Generate table oro_workflow_entity_acl_identity
     *
     * @param Schema $schema
     * @param string $tableName
     */
    public static function oroWorkflowEntityAclIdentityTable(Schema $schema, $tableName = null)
    {
        /** Generate table oro_workflow_entity_acl_identity **/
        $table = $schema->createTable($tableName ? : 'oro_workflow_entity_acl_identity');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_entity_acl_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            ['workflow_entity_acl_id', 'entity_id', 'workflow_item_id'],
            'oro_workflow_entity_acl_identity_unique_idx'
        );
        $table->addIndex(['workflow_entity_acl_id'], 'IDX_367002F1160F68FB', []);
        $table->addIndex(['workflow_item_id'], 'IDX_367002F11023C4EE', []);
        $table->addIndex(['entity_id', 'entity_class'], 'oro_workflow_entity_acl_identity_idx', []);
        /** End of generate table oro_workflow_entity_acl_identity **/
    }

    /**
     * Generate foreign keys for table oro_workflow_definition
     *
     * @param Schema $schema
     */
    public static function oroWorkflowDefinitionForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_workflow_definition **/
        $table = $schema->getTable('oro_workflow_definition');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['start_step_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_workflow_definition **/
    }

    /**
     * Generate foreign keys for table oro_workflow_item
     *
     * @param Schema $schema
     */
    public static function oroWorkflowItemForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_workflow_item **/
        $table = $schema->getTable('oro_workflow_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_definition'),
            ['workflow_name'],
            ['name'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['current_step_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_workflow_item **/
    }

    /**
     * Generate foreign keys for table oro_workflow_step
     *
     * @param Schema $schema
     */
    public static function oroWorkflowStepForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_workflow_step **/
        $table = $schema->getTable('oro_workflow_step');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_definition'),
            ['workflow_name'],
            ['name'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_workflow_step **/
    }

    /**
     * Generate foreign keys for table oro_workflow_transition_log
     *
     * @param Schema $schema
     */
    public static function oroWorkflowTransitionLogForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_workflow_transition_log **/
        $table = $schema->getTable('oro_workflow_transition_log');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['step_to_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_item'),
            ['workflow_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['step_from_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_workflow_transition_log **/
    }

    /**
     * Generate foreign keys for table oro_workflow_entity_acl
     *
     * @param Schema $schema
     */
    public static function oroWorkflowEntityAclForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_workflow_entity_acl **/
        $table = $schema->getTable('oro_workflow_entity_acl');
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
        /** End of generate foreign keys for table oro_workflow_entity_acl **/
    }

    /**
     * Generate foreign keys for table oro_workflow_entity_acl_identity
     *
     * @param Schema $schema
     * @param string $tableName
     */
    public static function oroWorkflowEntityAclIdentityForeignKeys(Schema $schema, $tableName = null)
    {
        /** Generate foreign keys for table oro_workflow_entity_acl_identity **/
        $table = $schema->getTable($tableName ? : 'oro_workflow_entity_acl_identity');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_item'),
            ['workflow_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_entity_acl'),
            ['workflow_entity_acl_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_workflow_entity_acl_identity **/
    }
}
