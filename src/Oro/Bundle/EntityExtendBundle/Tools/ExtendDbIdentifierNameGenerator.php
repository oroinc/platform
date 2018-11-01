<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

/**
 * Provides methods to generate column/table/index names of extend entities
 */
class ExtendDbIdentifierNameGenerator extends DbIdentifierNameGenerator
{
    const CUSTOM_TABLE_PREFIX              = 'oro_ext_';
    const CUSTOM_TABLE_PRIMARY_KEY_COLUMN  = 'id';
    const ENUM_TABLE_PREFIX                = 'oro_enum_';
    const CUSTOM_MANY_TO_MANY_TABLE_PREFIX = 'oro_rel_';
    const CUSTOM_INDEX_PREFIX              = 'oro_idx_';
    const RELATION_COLUMN_SUFFIX           = '_id';
    const SNAPSHOT_COLUMN_SUFFIX           = '_ss';
    const RELATION_DEFAULT_COLUMN_PREFIX   = ExtendConfigDumper::DEFAULT_PREFIX;

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
     *
     * @return int
     */
    public function getMaxEnumCodeSize()
    {
        return $this->getMaxIdentifierSize() - strlen(self::ENUM_TABLE_PREFIX);
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
     *
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
     * Builds a table name for an enum entity
     *
     * @param string $enumCode
     * @param bool   $allowHash If TRUE and $enumCode exceeds a limit for an enum code
     *                          a table name is generated based on a hash
     *                          If FALSE an exception will be raised
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function generateEnumTableName($enumCode, $allowHash = false)
    {
        if (strlen($enumCode) > $this->getMaxEnumCodeSize()) {
            if (!$allowHash) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The enum code length must be less or equal %d characters. Code: %s.',
                        $this->getMaxEnumCodeSize(),
                        $enumCode
                    )
                );
            }

            $hash = dechex(crc32($enumCode));

            // try to build "good looking" name if it is possible
            $lastPos  = strrpos($enumCode, '_');
            $lastPart = $lastPos !== false ? substr($enumCode, $lastPos + 1) : null;
            if ($lastPart) {
                if (strlen($lastPart) === strlen($hash)) {
                    // suppose that the last part is a hash
                    return self::ENUM_TABLE_PREFIX . $hash . '_' . $lastPart;
                }
                if (strlen($lastPart) <= $this->getMaxEnumCodeSize() - strlen($hash) - 1) {
                    $lastPos = strrpos($enumCode, '_', -(strlen($enumCode) - $lastPos + 1));
                    if ($lastPos !== false) {
                        $longerLastPart = substr($enumCode, $lastPos + 1);
                        if (strlen($longerLastPart) <= $this->getMaxEnumCodeSize() - strlen($hash) - 1) {
                            return self::ENUM_TABLE_PREFIX . $hash . '_' . $longerLastPart;
                        }
                    }

                    return self::ENUM_TABLE_PREFIX . $hash . '_' . $lastPart;
                }
            }

            return
                self::ENUM_TABLE_PREFIX
                . $hash . '_'
                . substr($enumCode, -($this->getMaxEnumCodeSize() - strlen($hash) - 1));
        }

        return self::ENUM_TABLE_PREFIX . $enumCode;
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
