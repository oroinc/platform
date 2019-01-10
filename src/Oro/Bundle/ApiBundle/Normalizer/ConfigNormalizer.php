<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Prepares a configuration to be used by ObjectNormalizer.
 * This class should be synchronized with the config normalizer for EntitySerializer.
 * @see \Oro\Bundle\ApiBundle\Util\ConfigNormalizer
 */
class ConfigNormalizer
{
    /**
     * Prepares a configuration to be used by ObjectNormalizer
     *
     * @param EntityDefinitionConfig $config
     *
     * @return EntityDefinitionConfig
     */
    public function normalizeConfig(EntityDefinitionConfig $config)
    {
        $this->preNormalizeConfig($config);
        $this->doNormalizeConfig($config);
    }

    /**
     * Remembers the current config state before it will be normalized
     *
     * @param EntityDefinitionConfig $config
     *
     * @return array
     */
    protected function preNormalizeConfig(EntityDefinitionConfig $config)
    {
        $excludedFields = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                $excludedFields[] = $fieldName;
            }
            $targetConfig = $field->getTargetEntity();
            if (null !== $targetConfig) {
                // remember the field name of the collapsed association
                // it is required to return a correct representation of serialized data
                if ($field->isCollapsed()) {
                    $collapseFieldName = $this->getCollapseFieldName($targetConfig);
                    if ($collapseFieldName) {
                        $targetConfig->set(ConfigUtil::COLLAPSE_FIELD, $collapseFieldName);
                    }
                }
                $this->preNormalizeConfig($targetConfig);
            }
        }
        // remember the list of excluded fields, because the 'exclude' option
        // can be changed during config normalization
        // it is required to return a correct representation of serialized data
        if (!empty($excludedFields)) {
            $config->set(ConfigUtil::EXCLUDED_FIELDS, $excludedFields);
        }
    }

    /**
     * Performs the normalization of a configuration
     *
     * @param EntityDefinitionConfig $config
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function doNormalizeConfig(EntityDefinitionConfig $config)
    {
        $toRemove = [];
        $renamedFields = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if (DataType::isExtendedAssociation($field->getDataType())) {
                $field->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
            }
            $propertyPath = $field->getPropertyPath();
            if ($propertyPath) {
                if (ConfigUtil::IGNORE_PROPERTY_PATH === $propertyPath) {
                    $toRemove[] = $fieldName;
                } elseif (false === strpos($propertyPath, ConfigUtil::PATH_DELIMITER)) {
                    $renamedFields[$propertyPath] = $fieldName;
                }
            }
            if ($field->getDependsOn() && !$field->isExcluded()) {
                $this->processDependentFields($config, $field->getDependsOn());
            }
        }
        // remember the map of renamed fields to speed up the serialization
        if (!empty($renamedFields)) {
            $config->set(ConfigUtil::RENAMED_FIELDS, $renamedFields);
        }
        foreach ($toRemove as $fieldName) {
            $config->removeField($fieldName);
        }
        foreach ($fields as $field) {
            $targetConfig = $field->getTargetEntity();
            if (null !== $targetConfig) {
                $this->doNormalizeConfig($targetConfig);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $config
     *
     * @return string|null
     */
    protected function getCollapseFieldName(EntityDefinitionConfig $config)
    {
        $result = null;
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field->isExcluded()) {
                if ($result) {
                    $result = null;
                    break;
                }
                $result = $fieldName;
            }
        }

        return $result;
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
        $dependsOnFieldName = $config->findFieldNameByPropertyPath($dependsOnPropertyPath[0]);
        if (!$dependsOnFieldName) {
            return;
        }

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
