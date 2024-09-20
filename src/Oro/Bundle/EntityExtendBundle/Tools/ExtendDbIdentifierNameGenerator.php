<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

/**
 * Provides methods to generate column/table/index names of extend entities
 */
class ExtendDbIdentifierNameGenerator extends DbIdentifierNameGenerator
{
    public const CUSTOM_TABLE_PREFIX = 'oro_ext_';
    public const CUSTOM_TABLE_PRIMARY_KEY_COLUMN = 'id';
    public const CUSTOM_MANY_TO_MANY_TABLE_PREFIX = 'oro_rel_';
    public const CUSTOM_INDEX_PREFIX = 'oro_idx_';
    public const RELATION_COLUMN_SUFFIX = '_id';
    public const SNAPSHOT_COLUMN_SUFFIX = '_ss';
    public const RELATION_DEFAULT_COLUMN_PREFIX = ExtendConfigDumper::DEFAULT_PREFIX;
    public const MAX_ENUM_CODE_SIZE = 64;

    /**
     * Gets the max size of an custom entity name
     * The custom entity is an entity which has no PHP class in any bundle. The definition of such entity is
     * created automatically in Symfony cache
     *
     * @return int
     */
    public function getMaxCustomEntityNameSize()
    {
        return $this->getMaxIdentifierSize() - strlen(self::CUSTOM_TABLE_PREFIX);
    }

    /**
     * Gets the max size of an enum code
     */
    public function getMaxEnumCodeSize(): int
    {
        return self::MAX_ENUM_CODE_SIZE;
    }

    /**
     * Gets the max size of an custom entity field name
     * The custom entity is an entity which has no PHP class in any bundle. The definition of such entity is
     * created automatically in Symfony cache
     *
     * @return int
     */
    public function getMaxCustomEntityFieldNameSize()
    {
        $subtractSize = max(
            strlen(self::RELATION_DEFAULT_COLUMN_PREFIX),
            strlen(self::RELATION_COLUMN_SUFFIX),
            strlen(self::SNAPSHOT_COLUMN_SUFFIX)
        );

        return $this->getMaxIdentifierSize() - $subtractSize;
    }

    /**
     * Builds a column name for a one-to-many relation
     *
     * @param string $entityClassName
     * @param string $associationName
     * @param string $suffix
     *
     * @return string
     */
    public function generateOneToManyRelationColumnName(
        $entityClassName,
        $associationName,
        $suffix = self::RELATION_COLUMN_SUFFIX
    ) {
        return sprintf(
            '%s%s',
            ExtendHelper::buildToManyRelationTargetFieldName($entityClassName, $associationName),
            $suffix
        );
    }

    /**
     * Builds a column name for a relation
     *
     * @param string $associationName
     * @param string $suffix
     *
     * @return string
     */
    public function generateRelationColumnName($associationName, $suffix = self::RELATION_COLUMN_SUFFIX)
    {
        return sprintf('%s%s', $associationName, $suffix);
    }

    /**
     * Builds a column name for a default relation
     *
     * @param string $associationName
     * @param string $suffix
     *
     * @return string
     */
    public function generateRelationDefaultColumnName($associationName, $suffix = self::RELATION_COLUMN_SUFFIX)
    {
        return sprintf(
            '%s%s%s',
            self::RELATION_DEFAULT_COLUMN_PREFIX,
            $associationName,
            $suffix
        );
    }

    /**
     * Builds a table name for a custom entity
     * The custom entity is an entity which has no PHP class in any bundle. The definition of such entity is
     * created automatically in Symfony cache
     *
     * @param string $entityClassName
     * @return string
     * @throws \InvalidArgumentException
     */
    public function generateCustomEntityTableName($entityClassName)
    {
        if (!ExtendHelper::isCustomEntity($entityClassName)) {
            throw new \InvalidArgumentException(
                sprintf('The "%s" must be a custom entity.', $entityClassName)
            );
        }

        $entityName = substr($entityClassName, strlen(ExtendHelper::ENTITY_NAMESPACE));
        if (empty($entityName) || !preg_match('/^[A-Za-z][\w]+$/', $entityName)) {
            throw new \InvalidArgumentException(sprintf('Invalid entity name. Class: %s.', $entityClassName));
        }
        if (strlen($entityName) > $this->getMaxCustomEntityNameSize()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Entity name length must be less or equal %d characters. Class: %s.',
                    $this->getMaxCustomEntityNameSize(),
                    $entityClassName
                )
            );
        }

        return self::CUSTOM_TABLE_PREFIX . strtolower($entityName);
    }

    /**
     * Gets the name of a primary key column for a custom entity
     * The custom entity is an entity which has no PHP class in any bundle. The definition of such entity is
     * created automatically in Symfony cache
     *
     * @return string
     */
    public function getCustomEntityPrimaryKeyColumnName()
    {
        return self::CUSTOM_TABLE_PRIMARY_KEY_COLUMN;
    }

    /**
     * Builds the name of a join table for many-to-many relation
     *
     * @param string $entityClassName
     * @param string $associationName
     * @param string $targetEntityClassName
     * @return string
     */
    public function generateManyToManyJoinTableName($entityClassName, $associationName, $targetEntityClassName)
    {
        // remove ending underscore (_) char
        $prefix = substr(self::CUSTOM_MANY_TO_MANY_TABLE_PREFIX, 0, -1);

        return $this->generateIdentifierName(
            [
                ExtendHelper::getShortClassName($entityClassName),
                ExtendHelper::getShortClassName($targetEntityClassName)
            ],
            [$associationName],
            $prefix,
            false
        );
    }

    /**
     * Builds the name of a column in a join table for a many-to-many relation
     *
     * @param string      $entityClassName
     * @param string      $suffix
     * @param string|null $prefix
     *
     * @return string
     */
    public function generateManyToManyJoinTableColumnName(
        $entityClassName,
        $suffix = self::RELATION_COLUMN_SUFFIX,
        $prefix = null
    ) {
        return sprintf(
            '%s%s%s',
            $prefix,
            strtolower(ExtendHelper::getShortClassName($entityClassName)),
            $suffix
        );
    }

    /**
     * Builds a column name for a field that is used to store selected options for multiple enums
     * This column is required to avoid group by clause when multiple enum is shown in a datagrid
     *
     * @param string $associationName
     *
     * @return string
     */
    public static function generateMultiEnumSnapshotColumnName($associationName)
    {
        return $associationName . self::SNAPSHOT_COLUMN_SUFFIX;
    }

    /**
     * @param string $entityClassName
     * @param string $fieldName
     * @return string
     */
    public function generateIndexNameForExtendFieldVisibleInGrid($entityClassName, $fieldName)
    {
        $entityClassName = ExtendHelper::getShortClassName($entityClassName);
        // remove ending underscore (_) char
        $prefix = substr(self::CUSTOM_INDEX_PREFIX, 0, -1);

        return $this->generateIdentifierName(
            $entityClassName,
            [$fieldName],
            $prefix,
            false
        );
    }
}
