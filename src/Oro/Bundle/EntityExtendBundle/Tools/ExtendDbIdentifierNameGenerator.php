<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class ExtendDbIdentifierNameGenerator extends DbIdentifierNameGenerator
{
    const CUSTOM_TABLE_PREFIX              = 'oro_ext_';
    const CUSTOM_MANY_TO_MANY_TABLE_PREFIX = 'oro_rel_';
    const CUSTOM_INDEX_PREFIX              = 'oro_idx';

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

        $entityName = substr($entityClassName, strlen(ExtendConfigDumper::ENTITY));
        if (empty($entityName) || !preg_match('/^[A-Za-z][\w]+$/', $entityName)) {
            throw new \InvalidArgumentException(sprintf('Invalid entity name. Class: %s.', $entityClassName));
        }
        if (strlen($entityName) > $this->getMaxCustomEntityNameSize()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Entity name length must be less or equal %d characters. Class: %s.',
                    $this->getMaxIdentifierSize() - strlen(self::CUSTOM_TABLE_PREFIX),
                    $entityClassName
                )
            );
        }

        return self::CUSTOM_TABLE_PREFIX . strtolower($entityName);
    }

    /**
     * Builds a table name for many-to-many relation
     *
     * @param string $entityClassName
     * @param string $fieldName
     * @param string $targetEntityClassName
     * @return string
     */
    public function generateManyToManyJoinTableName($entityClassName, $fieldName, $targetEntityClassName)
    {
        $selfParts       = explode('\\', $entityClassName);
        $entityClassName = array_pop($selfParts);

        $targetParts           = explode('\\', $targetEntityClassName);
        $targetEntityClassName = array_pop($targetParts);

        $prefix = substr(
            self::CUSTOM_MANY_TO_MANY_TABLE_PREFIX,
            0,
            strlen(self::CUSTOM_MANY_TO_MANY_TABLE_PREFIX) - 1
        );

        return $this->generateIdentifierName(
            [$entityClassName, $targetEntityClassName],
            [$fieldName],
            $prefix,
            false
        );
    }

    /**
     * @param string $entityClassName
     * @param string $fieldName
     * @return string
     */
    public function generateIndexNameForExtendFieldVisibleInGrid($entityClassName, $fieldName)
    {
        $selfParts       = explode('\\', $entityClassName);
        $entityClassName = array_pop($selfParts);

        return $this->generateIdentifierName($entityClassName, [$fieldName], self::CUSTOM_INDEX_PREFIX, false);
    }
}
