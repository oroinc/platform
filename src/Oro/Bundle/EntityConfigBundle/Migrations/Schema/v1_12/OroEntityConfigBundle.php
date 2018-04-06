<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroEntityConfigBundle implements Migration, AttachmentExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    const MAX_IMAGE_SIZE_IN_MB = 10;
    const THUMBNAIL_WIDTH_SIZE_IN_PX = 100;
    const THUMBNAIL_HEIGHT_SIZE_IN_PX = 100;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
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
        $table = $schema->getTable('oro_attribute_group');
        $table->addColumn('code', 'string', ['length' => 255, 'notnull' => false]);
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
        $table->addColumn('attribute_family_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
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
