<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_0\RenameExtendTablesAndColumns;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEntityExtendBundleInstaller extends RenameExtendTablesAndColumns implements Installation
{
    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_14';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        // rename should not be performed during a fresh installation
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            parent::up($schema, $queries);
        }
        $this->createOroEnumTable($schema);
        $this->createOroEnumOptionTransTable($schema);
    }

    private function createOroEnumTable(Schema $schema): void
    {
        if ($schema->hasTable('oro_enum_option')) {
            return;
        }
        $table = $schema->createTable('oro_enum_option');
        $table->addColumn('id', 'string', [
            'length' => ExtendHelper::MAX_ENUM_ID_LENGTH,
            OroOptions::KEY => [
                'entity' => [
                    'label' => ExtendHelper::getEnumTranslationKey('label', fieldName: 'id'),
                    'description' => ExtendHelper::getEnumTranslationKey('description', fieldName: 'id')
                ],
                'importexport' => [
                    'identity' => true,
                ],
            ]
        ]);
        $table->addColumn('internal_id', 'string', ['length' => ExtendHelper::MAX_ENUM_INTERNAL_ID_LENGTH]);
        $table->addColumn('enum_code', 'string', ['length' => 64]);
        $table->addColumn(
            'name',
            'string',
            [
                'length' => 255,
                OroOptions::KEY => [
                    'entity' => [
                        'label' => ExtendHelper::getEnumTranslationKey('label', fieldName: 'name'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', fieldName: 'name')
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                    ],
                ],
            ]
        );
        $table->addColumn(
            'priority',
            'integer',
            [
                OroOptions::KEY => [
                    'entity' => [
                        'label' => ExtendHelper::getEnumTranslationKey('label', fieldName: 'priority'),
                        'description' => ExtendHelper::getEnumTranslationKey(
                            'description',
                            fieldName: 'priority'
                        )
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                    ]
                ]
            ]
        );
        $table->addColumn(
            'is_default',
            'boolean',
            [
                OroOptions::KEY => [
                    ExtendOptionsManager::FIELD_NAME_OPTION => 'default',
                    'entity' => [
                        'label' => ExtendHelper::getEnumTranslationKey('label', fieldName: 'default'),
                        'description' => ExtendHelper::getEnumTranslationKey(
                            'description',
                            fieldName: 'default'
                        )
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                    ]
                ]
            ]
        );
        $table->setPrimaryKey(['id']);
        $table->addIndex(['enum_code'], 'oro_enum_code_idx');

        $options = [
            ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
            ExtendOptionsManager::ENTITY_CLASS_OPTION => EnumOption::class,
            'entity' => [
                'label' => ExtendHelper::getEnumTranslationKey('label', 'option'),
                'plural_label' => ExtendHelper::getEnumTranslationKey('plural_label', 'option'),
                'description' => ExtendHelper::getEnumTranslationKey('description', 'option')
            ],
            'extend' => [
                'owner' => ExtendScope::OWNER_SYSTEM,
                'is_extend' => true,
                'table' => 'oro_enum_option',
                'inherit' => EnumOptionInterface::class
            ],
        ];
        $table->addOption(OroOptions::KEY, $options);
    }

    private function createOroEnumOptionTransTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_enum_option_trans');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => ExtendHelper::MAX_ENUM_ID_LENGTH]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 16]);
        $table->addColumn('object_class', 'string', ['length' => 191]);
        $table->addColumn('field', 'string', ['length' => 4]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'oro_enum_option_trans_idx');
    }
}
