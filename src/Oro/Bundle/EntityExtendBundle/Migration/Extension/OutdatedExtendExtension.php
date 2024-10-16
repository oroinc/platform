<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides an ability to create outdated extended enum tables and fields.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OutdatedExtendExtension extends ExtendExtension
{
    use ExtendNameGeneratorAwareTrait;

    private const ALLOWED_IDENTITY_FIELDS = ['id', 'name'];
    private const DEFAULT_IDENTITY_FIELDS = ['id'];
    private const MAX_ENUM_VALUE_ID_LENGTH = 32;
    private const ENTITY_NAMESPACE = 'Extend\\Entity\\';

    /**
     * Creates a table that is used to store enum values for the enum with the given code.
     *
     * @param Schema $schema
     * @param string $enumCode The unique identifier of an enum
     * @param bool $isMultiple Indicates whether several options can be selected for this enum
     *                                  or it supports only one selected option
     * @param bool $isPublic Indicates whether this enum can be used by any entity or
     *                                  it is designed to use in one entity only
     * @param bool|string[] $immutable Indicates whether the changing the list of enum values and
     *                                  public flag is allowed or not. More details can be found
     *                                  in entity_config.yml
     * @param array $options
     * @param array $identityFields
     *
     * @return Table A table that is used to store enum values
     *
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function createOutdatedEnum(
        Schema $schema,
        $enumCode,
        $isMultiple = false,
        $isPublic = false,
        $immutable = false,
        array $options = [],
        array $identityFields = self::DEFAULT_IDENTITY_FIELDS
    ) {
        if ($enumCode !== ExtendHelper::buildEnumCode($enumCode)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The enum code "%s" must contain only lower alphabetical symbols, numbers and underscore.',
                    $enumCode
                )
            );
        }

        if (empty($identityFields)) {
            throw new \InvalidArgumentException('At least one identify field is required.');
        }

        if ($invalidIdentifyFields = array_diff($identityFields, self::ALLOWED_IDENTITY_FIELDS)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The identification fields can only be: %s. Current invalid fields: %s.',
                    implode(', ', self::ALLOWED_IDENTITY_FIELDS),
                    implode(', ', $invalidIdentifyFields)
                )
            );
        }

        $tableName = self::generateEnumTableName($enumCode);
        $className = self::buildEnumValueClassName($enumCode);

        $options = array_replace_recursive(
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                ExtendOptionsManager::ENTITY_CLASS_OPTION => $className,
                'entity' => [
                    'label' => ExtendHelper::getEnumTranslationKey('label', $enumCode),
                    'plural_label' => ExtendHelper::getEnumTranslationKey('plural_label', $enumCode),
                    'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode)
                ],
                'extend' => [
                    'owner' => ExtendScope::OWNER_SYSTEM,
                    'is_extend' => true,
                    'table' => $tableName,
                    'inherit' => 'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue'
                ],
                'enum' => [
                    'code' => $enumCode,
                    'public' => $isPublic,
                    'multiple' => $isMultiple
                ]
            ],
            $options
        );
        if ($immutable) {
            $options['enum']['immutable'] = true;
        }

        $table = $schema->createTable($tableName);
        $this->entityMetadataHelper->registerEntityClass($tableName, $className);
        $table->addOption(OroOptions::KEY, $options);

        $table->addColumn(
            'id',
            'string',
            [
                'length' => self::MAX_ENUM_VALUE_ID_LENGTH,
                OroOptions::KEY => [
                    'entity' => [
                        'label' => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'id'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'id')
                    ],
                    'importexport' => [
                        'identity' => in_array('id', $identityFields, true),
                    ],
                ]
            ]
        );
        $table->addColumn(
            'name',
            'string',
            [
                'length' => 255,
                OroOptions::KEY => [
                    'entity' => [
                        'label' => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'name'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'name')
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                    ],
                    'importexport' => [
                        'identity' => in_array('name', $identityFields, true),
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
                        'label' => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'priority'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'priority')
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
                        'label' => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'default'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'default')
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                    ]
                ]
            ]
        );
        $table->setPrimaryKey(['id']);

        return $table;
    }

    /**
     * Adds enumerable field
     *
     * Take in attention that this method creates new private enum if the enum with the given code
     * is not exist yet. If you want to create a public enum use {@link createOutdatedEnum} method before.
     *
     * @param Schema $schema
     * @param Table|string $table A Table object or table name
     * @param string $associationName The name of a relation field
     * @param string $enumCode The target enum identifier
     * @param bool $isMultiple Indicates whether several options can be selected for this enum
     *                                       or it supports only one selected option
     * @param bool|string[] $immutable Indicates whether the changing the list of enum values and
     *                                       public flag is allowed or not. More details can be found
     *                                       in entity_config.yml
     * @param array $options
     * @param array $identityFields
     *
     * @return Table A table that is used to store enum values
     */
    public function addOutdatedEnumField(
        Schema $schema,
        $table,
        $associationName,
        $enumCode,
        $isMultiple = false,
        $immutable = false,
        array $options = [],
        array $identityFields = self::DEFAULT_IDENTITY_FIELDS
    ) {
        $enumTableName = self::generateEnumTableName($enumCode);
        $selfTable = $this->getTable($table, $schema);

        // make sure a table that is used to store enum values exists
        if (!$schema->hasTable($enumTableName)) {
            $enumTable = $this->createOutdatedEnum(
                $schema,
                $enumCode,
                $isMultiple,
                false,
                $immutable,
                [],
                $identityFields
            );
        } else {
            $enumTable = $this->getTable($enumTableName, $schema);
        }

        // create appropriate relation
        $options['enum']['enum_code'] = $enumCode;
        if ($isMultiple) {
            $options['extend']['without_default'] = true;
            $this->addManyToManyRelation(
                $schema,
                $selfTable,
                $associationName,
                $enumTableName,
                ['name'],
                ['name'],
                ['name'],
                $options,
                'multiEnum'
            );
            // create a column that will contain selected options
            // this column is required to avoid group by clause when multiple enum is shown in a datagrid
            $selfTable->addColumn(
                $this->nameGenerator->generateMultiEnumSnapshotColumnName($associationName),
                'string',
                [
                    'notnull' => false,
                    'length' => ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH
                ]
            );
        } else {
            $this->addManyToOneRelation(
                $schema,
                $selfTable,
                $associationName,
                $enumTableName,
                'name',
                $options,
                'enum'
            );
        }

        return $enumTable;
    }

    public static function generateEnumTableName(string $enumCode, $allowHash = false, $mxEnumCodeSize = 64): string
    {
        if (strlen($enumCode) > $mxEnumCodeSize) {
            if (!$allowHash) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The enum code length must be less or equal %d characters. Code: %s.',
                        $mxEnumCodeSize,
                        $enumCode
                    )
                );
            }

            $hash = dechex(crc32($enumCode));

            // try to build "good looking" name if it is possible
            $lastPos = strrpos($enumCode, '_');
            $lastPart = $lastPos !== false ? substr($enumCode, $lastPos + 1) : null;
            if ($lastPart) {
                if (strlen($lastPart) === strlen($hash)) {
                    // suppose that the last part is a hash
                    return 'oro_enum_' . $hash . '_' . $lastPart;
                }
                if (strlen($lastPart) <= $mxEnumCodeSize - strlen($hash) - 1) {
                    $lastPos = strrpos($enumCode, '_', -(strlen($enumCode) - $lastPos + 1));
                    if ($lastPos !== false) {
                        $longerLastPart = substr($enumCode, $lastPos + 1);
                        if (strlen($longerLastPart) <= $mxEnumCodeSize - strlen($hash) - 1) {
                            return 'oro_enum_' . $hash . '_' . $longerLastPart;
                        }
                    }

                    return 'oro_enum_' . $hash . '_' . $lastPart;
                }
            }

            return
                'oro_enum_'
                . $hash . '_'
                . substr($enumCode, -($mxEnumCodeSize - strlen($hash) - 1));
        }

        return 'oro_enum_' . $enumCode;
    }

    /**
     * Returns full class name for an entity is used to store values of the given enum.
     *
     * @param string $enumCode
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public static function buildEnumValueClassName(string $enumCode): string
    {
        if (empty($enumCode)) {
            throw new \InvalidArgumentException('$enumCode must not be empty.');
        }

        return self::ENTITY_NAMESPACE . 'EV_' . str_replace(' ', '_', ucwords(strtr($enumCode, '_-', '  ')));
    }
}
