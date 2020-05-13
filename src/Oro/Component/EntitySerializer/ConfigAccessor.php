<?php

namespace Oro\Component\EntitySerializer;

/**
 * Provides a set of methods to get information from entity configuration.
 */
class ConfigAccessor
{
    /**
     * @param string           $fieldName
     * @param FieldConfig|null $fieldConfig
     *
     * @return string
     */
    public function getPropertyPath(string $fieldName, FieldConfig $fieldConfig = null): string
    {
        if (null === $fieldConfig) {
            return $fieldName;
        }

        return $fieldConfig->getPropertyPath($fieldName);
    }

    /**
     * @param EntityConfig $config
     * @param string       $fieldName
     *
     * @return EntityConfig
     */
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

    /**
     * @param EntityConfig $config
     * @param string       $fieldName
     *
     * @return AssociationQuery|null
     */
    public function getAssociationQuery(EntityConfig $config, string $fieldName): ?AssociationQuery
    {
        $fieldConfig = $config->getField($fieldName);
        if (null === $fieldConfig) {
            return null;
        }

        return $fieldConfig->get(ConfigUtil::ASSOCIATION_QUERY);
    }
}
