<?php

namespace Oro\Bundle\DigitalAssetBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * OroDigitalAssetBundle installer class:
 * - creates oro_digital_asset table for DigitalAsset entity
 * - adds digitalAsset relation to File entity
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroDigitalAssetBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    private $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension): void
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroDigitalAssetTitleTable($schema);
        $this->createOroDigitalAssetTable($schema);
        $this->addDigitalAssetRelationToFile($schema);

        /** Foreign keys generation **/
        $this->addOroDigitalAssetTitleForeignKeys($schema);
        $this->addOroDigitalAssetForeignKeys($schema);
    }

    /**
     * Create oro_digital_asset_title table
     */
    private function createOroDigitalAssetTitleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_digital_asset_title');
        $table->addColumn('digital_asset_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);

        $table->addIndex(['digital_asset_id'], 'idx_f8bb3b43e52f7284', []);

        $table->setPrimaryKey(['digital_asset_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_f8bb3b43eb576e89');
    }

    /**
     * Create oro_digital_asset table
     */
    private function createOroDigitalAssetTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_digital_asset');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('source_file_id', 'integer', []);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);

        $table->addIndex(['created_at'], 'created_at_idx', []);
        $table->addIndex(['user_owner_id'], 'idx_a886b3579eb185f9', []);
        $table->addIndex(['organization_id'], 'idx_a886b35732c8a3de', []);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['source_file_id'], 'uniq_a886b35793cb796c');
    }

    private function addDigitalAssetRelationToFile(Schema $schema): void
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'oro_attachment_file',
            'digitalAsset',
            'oro_digital_asset',
            'id',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'is_extend' => true,
                    'nullable' => true,
                    'on_delete' => 'SET NULL',
                ],
                'importexport' => [
                    'excluded' => true,
                ],
                'dataaudit' => [
                    'auditable' => false,
                ],
                'form' => [
                    'is_enabled' => false,
                ],
                'datagrid' => [
                    'is_visible' => DatagridScope::IS_VISIBLE_FALSE,
                    'show_filter' => false,
                ],
                'view' => [
                    'is_displayable' => false,
                ],
                'email' => [
                    'available_in_template' => false,
                ],
                'search' => [
                    'searchable' => false,
                ],
            ]
        );

        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            'oro_attachment_file',
            'digitalAsset',
            'oro_digital_asset',
            'childFiles',
            ['id'],
            ['id'],
            ['id'],
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'fetch' => 'extra_lazy',
                ],
                'importexport' => [
                    'excluded' => true,
                ],
                'dataaudit' => [
                    'auditable' => false,
                ],
                'form' => [
                    'is_enabled' => false,
                ],
                'datagrid' => [
                    'is_visible' => DatagridScope::IS_VISIBLE_FALSE,
                    'show_filter' => false,
                ],
                'view' => [
                    'is_displayable' => false,
                ],
                'email' => [
                    'available_in_template' => false,
                ],
                'search' => [
                    'searchable' => false,
                ],
            ]
        );
    }

    /**
     * Add oro_digital_asset_title foreign keys.
     */
    private function addOroDigitalAssetTitleForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_digital_asset_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_digital_asset'),
            ['digital_asset_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_digital_asset foreign keys.
     */
    private function addOroDigitalAssetForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_digital_asset');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['source_file_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
