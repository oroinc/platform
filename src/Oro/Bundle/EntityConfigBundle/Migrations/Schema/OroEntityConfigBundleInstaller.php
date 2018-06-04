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

    const MAX_IMAGE_SIZE_IN_MB = 10;
    const THUMBNAIL_WIDTH_SIZE_IN_PX = 100;
    const THUMBNAIL_HEIGHT_SIZE_IN_PX = 100;

    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_14_2';
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroEntityConfigTable($schema);
        $this->createOroEntityConfigFieldTable($schema);
        $this->createOroEntityConfigIndexValueTable($schema);
        $this->createOroEntityConfigLogTable($schema);
        $this->createOroEntityConfigLogDiffTable($schema);

        /** Foreign keys generation **/
        $this->addOroEntityConfigFieldForeignKeys($schema);
        $this->addOroEntityConfigIndexValueForeignKeys($schema);
        $this->addOroEntityConfigLogForeignKeys($schema);
        $this->addOroEntityConfigLogDiffForeignKeys($schema);

        $this->createOroAttributeFamilyTable($schema);
        $this->createOroAttributeFamilyLabelTable($schema);
        $this->createOroAttributeGroupTable($schema);
        $this->createOroAttributeGroupLabelTable($schema);
        $this->createOroAttributeGroupRelTable($schema);
        $this->addOroAttributeFamilyForeignKeys($schema);
        $this->addOroAttributeFamilyLabelForeignKeys($schema);
        $this->addOroAttributeGroupLabelForeignKeys($schema);
        $this->addOroAttributeGroupRelForeignKeys($schema);
        $this->addOroAttributeGroupForeignKeys($schema);
        $this->addAttributeFamilyImageAssociation($schema);
        $this->addOrganizationForeignKey($schema);
    }

    /**
     * Create oro_entity_config table
     *
     * @param Schema $schema
     */
    protected function createOroEntityConfigTable(Schema $schema)
    {
        $table = $schema->createTable('oro_entity_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('class_name', 'string', ['length' => 255]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', ['notnull' => false]);
        $table->addColumn('mode', 'string', ['length' => 8]);
        $table->addColumn('data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['class_name'], 'oro_entity_config_uq');
    }

    /**
     * Create oro_entity_config_field table
     *
     * @param Schema $schema
     */
    protected function createOroEntityConfigFieldTable(Schema $schema)
    {
        $table = $schema->createTable('oro_entity_config_field');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('field_name', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 60]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', ['notnull' => false]);
        $table->addColumn('mode', 'string', ['length' => 8]);
        $table->addColumn('data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_id'], 'IDX_63EC23F781257D5D', []);
    }

    /**
     * Create oro_entity_config_index_value table
     *
     * @param Schema $schema
     */
    protected function createOroEntityConfigIndexValueTable(Schema $schema)
    {
        $table = $schema->createTable('oro_entity_config_index_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('field_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('scope', 'string', ['length' => 255]);
        $table->addColumn('value', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_id'], 'IDX_256E3E9B81257D5D', []);
        $table->addIndex(['field_id'], 'IDX_256E3E9B443707B0', []);
        $table->addIndex(['scope', 'code', 'value', 'entity_id'], 'idx_entity_config_index_entity', []);
        $table->addIndex(['scope', 'code', 'value', 'field_id'], 'idx_entity_config_index_field', []);
    }

    /**
     * Create oro_entity_config_log table
     *
     * @param Schema $schema
     */
    protected function createOroEntityConfigLogTable(Schema $schema)
    {
        $table = $schema->createTable('oro_entity_config_log');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('logged_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_4A4961FBA76ED395', []);
    }

    /**
     * Create oro_entity_config_log_diff table
     *
     * @param Schema $schema
     */
    protected function createOroEntityConfigLogDiffTable(Schema $schema)
    {
        $table = $schema->createTable('oro_entity_config_log_diff');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('log_id', 'integer', ['notnull' => false]);
        $table->addColumn('class_name', 'string', ['length' => 100]);
        $table->addColumn('field_name', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('scope', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('diff', 'text', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['log_id'], 'IDX_D1F6D75AEA675D86', []);
    }

    /**
     * Add oro_entity_config_field foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroEntityConfigFieldForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroEntityConfigIndexValueForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroEntityConfigLogForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroEntityConfigLogDiffForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function createOroAttributeFamilyTable(Schema $schema)
    {
        $table = $schema->createTable('oro_attribute_family');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('is_enabled', 'boolean', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code']);
        $table->addIndex(['user_owner_id']);
        $table->addIndex(['organization_id']);
    }

    /**
     * Create oro_attribute_family_label table
     *
     * @param Schema $schema
     */
    protected function createOroAttributeFamilyLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_attribute_family_label');
        $table->addColumn('attribute_family_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['attribute_family_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
        $table->addIndex(['attribute_family_id']);
    }

    /**
     * Create oro_attribute_group table
     *
     * @param Schema $schema
     */
    protected function createOroAttributeGroupTable(Schema $schema)
    {
        $table = $schema->createTable('oro_attribute_group');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('attribute_family_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('is_visible', 'boolean', ['default' => '1']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['attribute_family_id']);
    }

    /**
     * Create oro_attribute_group_label table
     *
     * @param Schema $schema
     */
    protected function createOroAttributeGroupLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_attribute_group_label');
        $table->addColumn('attribute_group_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['attribute_group_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
        $table->addIndex(['attribute_group_id']);
    }

    /**
     * Create oro_attribute_group_rel table
     *
     * @param Schema $schema
     */
    protected function createOroAttributeGroupRelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_attribute_group_rel');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('attribute_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_config_field_id', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['entity_config_field_id', 'attribute_group_id'], 'oro_attribute_group_uidx');
        $table->addIndex(['attribute_group_id']);
    }

    /**
     * Add oro_attribute_family foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAttributeFamilyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_attribute_family');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_attribute_family_label foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAttributeFamilyLabelForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroAttributeGroupForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroAttributeGroupLabelForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroAttributeGroupRelForeignKeys(Schema $schema)
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
     * @param Schema $schema
     */
    public function addAttributeFamilyImageAssociation(Schema $schema)
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_attribute_family',
            'image',
            [],
            self::MAX_IMAGE_SIZE_IN_MB,
            self::THUMBNAIL_WIDTH_SIZE_IN_PX,
            self::THUMBNAIL_HEIGHT_SIZE_IN_PX
        );
    }

    /**
     * @param Schema $schema
     */
    public function addOrganizationForeignKey(Schema $schema)
    {
        $table = $schema->getTable('oro_attribute_family');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
