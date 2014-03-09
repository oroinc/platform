<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator as BaseGenerator;

class DbIdentifierNameGenerator extends BaseGenerator
{
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
        if (strlen($entityName) + strlen(ExtendConfigDumper::TABLE_PREFIX) > $this->getMaxIdentifierSize()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Entity name length must be less or equal %d characters. Class: %s.',
                    $this->getMaxIdentifierSize() - strlen(ExtendConfigDumper::TABLE_PREFIX),
                    $entityClassName
                )
            );
        }

        return ExtendConfigDumper::TABLE_PREFIX . strtolower($entityName);
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
        $vendorName      = ExtendHelper::isCustomEntity($entityClassName)
            ? 'oro'
            : array_shift($selfParts);
        $entityClassName = array_pop($selfParts);

        $targetParts           = explode('\\', $targetEntityClassName);
        $targetEntityClassName = array_pop($targetParts);

        return $this->generateIdentifierName([$fieldName], $vendorName, [$entityClassName, $targetEntityClassName]);
    }
}
