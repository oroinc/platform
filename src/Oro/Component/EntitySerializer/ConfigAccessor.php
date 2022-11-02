<?php

namespace Oro\Component\EntitySerializer;

/**
 * Provides a set of methods to get information from entity configuration.
 */
class ConfigAccessor
{
    public function getPropertyPath(string $fieldName, FieldConfig $fieldConfig = null): string
    {
        if (null === $fieldConfig) {
            return $fieldName;
        }

        return $fieldConfig->getPropertyPath($fieldName);
    }

    public function getTargetEntity(EntityConfig $config, string $fieldName): EntityConfig
    {
        $fieldConfig = $config->getField($fieldName);
        if (null === $fieldConfig) {
            return new InternalEntityConfig();
        }

        $targetConfig = $fieldConfig->getTargetEntity();
        if (null === $targetConfig) {
            $targetConfig = new InternalEntityConfig();
            $fieldConfig->setTargetEntity($targetConfig);
        }

        return $targetConfig;
    }

    public function getAssociationQuery(EntityConfig $config, string $fieldName): ?AssociationQuery
    {
        $fieldConfig = $config->getField($fieldName);
        if (null === $fieldConfig) {
            return null;
        }

        return $fieldConfig->get(ConfigUtil::ASSOCIATION_QUERY);
    }
}
