<?php

namespace Oro\Bundle\WorkflowBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroWorkflowBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_workflow_definition **/
        $table = $schema->createTable('oro_workflow_definition');
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('start_step_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('label', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('related_entity', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('entity_attribute_name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('steps_display_ordered', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('enabled', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('configuration', 'array', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['name']);
        $table->addIndex(['start_step_id'], 'IDX_6F737C368377424F', []);
        $table->addIndex(['enabled'], 'oro_workflow_definition_enabled_idx', []);
        /** End of generate table oro_workflow_definition **/

        /** Generate table oro_workflow_item **/
        $table = $schema->createTable('oro_workflow_item');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('workflow_name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('current_step_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('entity_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('closed', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('created', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('updated', 'datetime', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('data', 'text', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['entity_id', 'workflow_name'], 'oro_workflow_item_entity_definition_unq');
        $table->addIndex(['current_step_id'], 'IDX_169789AED9BF9B19', []);
        $table->addIndex(['workflow_name'], 'IDX_169789AE1BBC6E3D', []);
        /** End of generate table oro_workflow_item **/

        /** Generate table oro_workflow_step **/
        $table = $schema->createTable('oro_workflow_step');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('workflow_name', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('label', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('step_order', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['workflow_name', 'name'], 'oro_workflow_step_unique_idx');
        $table->addIndex(['workflow_name'], 'IDX_4A35528C1BBC6E3D', []);
        /** End of generate table oro_workflow_step **/

        /** Generate table oro_workflow_transition_log **/
        $table = $schema->createTable('oro_workflow_transition_log');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('step_to_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('workflow_item_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('step_from_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('transition', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('transition_date', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['workflow_item_id'], 'IDX_B3D57B301023C4EE', []);
        $table->addIndex(['step_from_id'], 'IDX_B3D57B30C8335FE4', []);
        $table->addIndex(['step_to_id'], 'IDX_B3D57B302C17BD9A', []);
        /** End of generate table oro_workflow_transition_log **/

        /** Generate table oro_workflow_entity_acl **/
        $table = $schema->createTable('oro_workflow_entity_acl');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('workflow_step_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('workflow_name', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('attribute', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('entity_class', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('updatable', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('deletable', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['workflow_name', 'attribute', 'workflow_step_id'], 'oro_workflow_acl_unique_idx');
        $table->addIndex(['workflow_name'], 'IDX_C54C5E5B1BBC6E3D', []);
        $table->addIndex(['workflow_step_id'], 'IDX_C54C5E5B71FE882C', []);
        /** End of generate table oro_workflow_entity_acl **/

        /** Generate table oro_workflow_entity_acl_identity **/
        $table = $schema->createTable('oro_workflow_entity_acl_identity');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('workflow_item_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('workflow_entity_acl_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('entity_class', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('entity_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['workflow_entity_acl_id', 'entity_id', 'workflow_item_id'], 'oro_workflow_entity_acl_identity_unique_idx');
        $table->addIndex(['workflow_entity_acl_id'], 'IDX_367002F1160F68FB', []);
        $table->addIndex(['workflow_item_id'], 'IDX_367002F11023C4EE', []);
        $table->addIndex(['entity_id', 'entity_class'], 'oro_workflow_entity_acl_identity_idx', []);
        /** End of generate table oro_workflow_entity_acl_identity **/

        /** Generate foreign keys for table oro_workflow_definition **/
        $table = $schema->getTable('oro_workflow_definition');
        $table->addForeignKeyConstraint($schema->getTable('oro_workflow_step'), ['start_step_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_workflow_definition **/

        /** Generate foreign keys for table oro_workflow_item **/
        $table = $schema->getTable('oro_workflow_item');
        $table->addForeignKeyConstraint($schema->getTable('oro_workflow_definition'), ['workflow_name'], ['name'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_workflow_step'), ['current_step_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_workflow_item **/

        /** Generate foreign keys for table oro_workflow_step **/
        $table = $schema->getTable('oro_workflow_step');
        $table->addForeignKeyConstraint($schema->getTable('oro_workflow_definition'), ['workflow_name'], ['name'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_workflow_step **/

        /** Generate foreign keys for table oro_workflow_transition_log **/
        $table = $schema->getTable('oro_workflow_transition_log');
        $table->addForeignKeyConstraint($schema->getTable('oro_workflow_step'), ['step_to_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_workflow_item'), ['workflow_item_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_workflow_step'), ['step_from_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_workflow_transition_log **/

        /** Generate foreign keys for table oro_workflow_entity_acl **/
        $table = $schema->getTable('oro_workflow_entity_acl');
        $table->addForeignKeyConstraint($schema->getTable('oro_workflow_step'), ['workflow_step_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_workflow_definition'), ['workflow_name'], ['name'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_workflow_entity_acl **/

        /** Generate foreign keys for table oro_workflow_entity_acl_identity **/
        $table = $schema->getTable('oro_workflow_entity_acl_identity');
        $table->addForeignKeyConstraint($schema->getTable('oro_workflow_item'), ['workflow_item_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_workflow_entity_acl'), ['workflow_entity_acl_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_workflow_entity_acl_identity **/

        // @codingStandardsIgnoreEnd

        return [];
    }
}
