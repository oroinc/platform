<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEntityConfigBundleInstaller implements Installation, AttachmentExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_17';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroEntityConfigTable($schema);
        $this->createOroEntityConfigFieldTable($schema);
        $this->createOroEntityConfigIndexValueTable($schema);
        $this->createOroEntityConfigLogTable($schema);
        $this->createOroEntityConfigLogDiffTable($schema);
        $this->createOroAttributeFamilyTable($schema);
        $this->createOroAttributeFamilyLabelTable($schema);
        $this->createOroAttributeGroupTable($schema);
        $this->createOroAttributeGroupLabelTable($schema);
        $this->createOroAttributeGroupRelTable($schema);

        /** Foreign keys generation **/
        $this->addOroEntityConfigFieldForeignKeys($schema);
        $this->addOroEntityConfigIndexValueForeignKeys($schema);
        $this->addOroEntityConfigLogForeignKeys($schema);
        $this->addOroEntityConfigLogDiffForeignKeys($schema);
        $this->addOroAttributeFamilyLabelForeignKeys($schema);
        $this->addOroAttributeGroupLabelForeignKeys($schema);
        $this->addOroAttributeGroupRelForeignKeys($schema);
        $this->addOroAttributeGroupForeignKeys($schema);
        $this->addOroAttributeFamilyForeignKeys($schema);

        $this->addAttributeFamilyImageAssociation($schema);
    }

    /**
     * Create oro_entity_config table
     */
    private function createOroEntityConfigTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_entity_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('class_name', 'string', ['length' => 255]);
        $table->addColumn('created', 'datetime');
        $table->addColumn('updated', 'datetime', ['notnull' => false]);
        $table->addColumn('mode', 'string', ['length' => 8]);
        $table->addColumn('data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['class_name'], 'oro_entity_config_uq');
    }

    /**
     * Create oro_entity_config_field table
     */
    private function createOroEntityConfigFieldTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_entity_config_field');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('field_name', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 60]);
        $table->addColumn('created', 'datetime');
        $table->addColumn('updated', 'datetime', ['notnull' => false]);
        $table->addColumn('mode', 'string', ['length' => 8]);
        $table->addColumn('data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_id'], 'IDX_63EC23F781257D5D');
    }

    /**
     * Create oro_entity_config_index_value table
     */
    private function createOroEntityConfigIndexValueTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_entity_config_index_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('field_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('scope', 'string', ['length' => 255]);
        $table->addColumn('value', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_id'], 'IDX_256E3E9B81257D5D');
        $table->addIndex(['field_id'], 'IDX_256E3E9B443707B0');
        $table->addIndex(['scope', 'code', 'value', 'entity_id'], 'idx_entity_config_index_entity');
        $table->addIndex(['scope', 'code', 'value', 'field_id'], 'idx_entity_config_index_field');
    }

    /**
     * Create oro_entity_config_log table
     */
    private function createOroEntityConfigLogTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_entity_config_log');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('logged_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_4A4961FBA76ED395');
    }

    /**
     * Create oro_entity_config_log_diff table
     */
    private function createOroEntityConfigLogDiffTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_entity_config_log_diff');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('log_id', 'integer', ['notnull' => false]);
        $table->addColumn('class_name', 'string', ['length' => 100]);
        $table->addColumn('field_name', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('scope', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('diff', 'text');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['log_id'], 'IDX_D1F6D75AEA675D86');
    }

    /**
     * Add oro_entity_config_field foreign keys.
     */
    private function addOroEntityConfigFieldForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_entity_config_field');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config'),
            ['entity_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_entity_config_index_value foreign keys.
     */
    private function addOroEntityConfigIndexValueForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_entity_config_index_value');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config_field'),
            ['field_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config'),
            ['entity_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_entity_config_log foreign keys.
     */
    private function addOroEntityConfigLogForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_entity_config_log');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_entity_config_log_diff foreign keys.
     */
    private function addOroEntityConfigLogDiffForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_entity_config_log_diff');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config_log'),
            ['log_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_attribute_family table
     */
    private function createOroAttributeFamilyTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_attribute_family');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('is_enabled', 'boolean');
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code', 'organization_id'], 'oro_attribute_family_code_org_uidx');
        $table->addIndex(['organization_id']);
    }

    /**
     * Create oro_attribute_family_label table
     */
    private function createOroAttributeFamilyLabelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_attribute_family_label');
        $table->addColumn('attribute_family_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['attribute_family_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
        $table->addIndex(['attribute_family_id']);
    }

    /**
     * Create oro_attribute_group table
     */
    private function createOroAttributeGroupTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_attribute_group');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('attribute_family_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('is_visible', 'boolean', ['default' => '1']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['attribute_family_id']);
    }

    /**
     * Create oro_attribute_group_label table
     */
    private function createOroAttributeGroupLabelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_attribute_group_label');
        $table->addColumn('attribute_group_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['attribute_group_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
        $table->addIndex(['attribute_group_id']);
    }

    /**
     * Create oro_attribute_group_rel table
     */
    private function createOroAttributeGroupRelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_attribute_group_rel');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('attribute_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_config_field_id', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['entity_config_field_id', 'attribute_group_id'], 'oro_attribute_group_uidx');
        $table->addIndex(['attribute_group_id']);
    }

    /**
     * Add oro_attribute_family_label foreign keys.
     */
    private function addOroAttributeFamilyLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_attribute_family_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attribute_family'),
            ['attribute_family_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_attribute_group foreign keys.
     */
    private function addOroAttributeGroupForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_attribute_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attribute_family'),
            ['attribute_family_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add oro_attribute_group_label foreign keys.
     */
    private function addOroAttributeGroupLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_attribute_group_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attribute_group'),
            ['attribute_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_attribute_group_rel foreign keys.
     */
    private function addOroAttributeGroupRelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_attribute_group_rel');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attribute_group'),
            ['attribute_group_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add oro_attribute_family foreign keys.
     */
    private function addOroAttributeFamilyForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_attribute_family');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    private function addAttributeFamilyImageAssociation(Schema $schema): void
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_attribute_family',
            'image',
            [
                'attachment' => [
                    'acl_protected' => false,
                ]
            ],
            10,
            100,
            100
        );
    }
}
