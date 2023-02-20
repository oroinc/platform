<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * Provides a set of static methods to simplify working with a configuration of associations.
 */
class AssociationConfigUtil
{
    /**
     * Gets the class name of a target entity for the given association.
     */
    public static function getAssociationTargetClass(
        EntityDefinitionFieldConfig $association,
        EntityDefinitionConfig $config
    ): ?string {
        $targetClass = $association->getTargetClass();
        if (!$targetClass && null === $association->getTargetEntity()) {
            $propertyPath = $association->getPropertyPath();
            if ($propertyPath) {
                $targetField = $config->findFieldByPath($propertyPath);
                if (null !== $targetField) {
                    $targetClass = $targetField->getTargetClass();
                }
            }
        }

        return $targetClass;
    }

    /**
     * Gets a configuration for the given association.
     */
    public static function getAssociationConfig(
        EntityDefinitionFieldConfig $association,
        EntityDefinitionConfig $config
    ): ?EntityDefinitionFieldConfig {
        if (null !== $association->getTargetEntity()) {
            return $association;
        }

        $propertyPath = $association->getPropertyPath();
        if (!$propertyPath) {
            return null;
        }

        return $config->findFieldByPath($propertyPath);
    }

    /**
     * Gets the name(s) of identifier field(s) of the given entity.
     *
     * @param EntityDefinitionConfig $config
     *
     * @return string|string[]|null
     */
    public static function getEntityIdentifierFieldName(EntityDefinitionConfig $config): string|array|null
    {
        $fieldNames = $config->getIdentifierFieldNames();
        $numberOfFields = \count($fieldNames);
        if (0 === $numberOfFields) {
            return null;
        }
        if (1 === $numberOfFields) {
            return reset($fieldNames);
        }

        return $fieldNames;
    }
}
