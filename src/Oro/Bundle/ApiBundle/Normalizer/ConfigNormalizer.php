<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * This class should be synchronized with the config normalizer for EntitySerializer.
 * @see Oro\Bundle\ApiBundle\Util\ConfigNormalizer
 */
class ConfigNormalizer
{
    /**
     * Normalizes a configuration of the ObjectNormalizer
     *
     * @param EntityDefinitionConfig $config
     *
     * @return EntityDefinitionConfig
     */
    public function normalizeConfig(EntityDefinitionConfig $config)
    {
        $toRemove = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            $propertyPath = $field->getPropertyPath();
            if (ConfigUtil::IGNORE_PROPERTY_PATH === $propertyPath) {
                $toRemove[] = $fieldName;
            }
            if ($field->getDependsOn() && !$field->isExcluded()) {
                $this->processDependentFields($config, $field->getDependsOn());
            }
        }
        foreach ($toRemove as $fieldName) {
            $config->removeField($fieldName);
        }
        foreach ($fields as $field) {
            $targetConfig = $field->getTargetEntity();
            if (null !== $targetConfig) {
                $this->normalizeConfig($targetConfig);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param string[]               $dependsOn
     */
    protected function processDependentFields(EntityDefinitionConfig $config, array $dependsOn)
    {
        foreach ($dependsOn as $dependsOnPropertyPath) {
            $this->processDependentField($config, ConfigUtil::explodePropertyPath($dependsOnPropertyPath));
        }
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param string[]               $dependsOnPropertyPath
     */
    protected function processDependentField(EntityDefinitionConfig $config, array $dependsOnPropertyPath)
    {
        $dependsOnFieldName = $dependsOnPropertyPath[0];
        $dependsOnField = $config->getOrAddField($dependsOnFieldName);
        if ($dependsOnField->isExcluded()) {
            $dependsOnField->setExcluded(false);
            $dependsOn = $dependsOnField->getDependsOn();
            if ($dependsOn) {
                $this->processDependentFields($config, $dependsOn);
            }
        }
        if (count($dependsOnPropertyPath) > 1) {
            $targetConfig = $dependsOnField->getOrCreateTargetEntity();
            $this->processDependentField($targetConfig, array_slice($dependsOnPropertyPath, 1));
        }
    }
}
