<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\UpsertConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\EntitySerializer\EntityConfigInterface;
use Oro\Component\EntitySerializer\FieldConfigInterface;

/**
 * Provides a method to merge entity configuration with configuration of parent entity.
 */
class MergeParentResourceHelper
{
    private ConfigProvider $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    public function mergeParentResourceConfig(ConfigContext $context, string $parentResourceClass): void
    {
        $parentConfig = $this->configProvider->getConfig(
            $parentResourceClass,
            $context->getVersion(),
            $context->getRequestType(),
            $context->getExtras()
        );
        $parentDefinition = $parentConfig->getDefinition();
        if (null !== $parentDefinition) {
            $parentDefinition->setExclusionPolicy(null);
            if ($context->hasResult()) {
                $this->mergeDefinition($parentDefinition, $context->getResult());
            }
            $parentDefinition->setParentResourceClass($parentResourceClass);
            $context->setResult($parentDefinition);
            $parentConfig->remove(ConfigUtil::DEFINITION);
        }
        foreach ($parentConfig as $sectionName => $parentSection) {
            if ($parentSection instanceof EntityConfigInterface) {
                $parentSection->setExclusionPolicy(null);
                if ($context->hasConfigSection($sectionName)) {
                    $this->mergeConfigSection($parentSection, $context->getConfigSection($sectionName));
                }
                $context->setConfigSection($sectionName, $parentSection);
            }
        }
    }

    private function mergeDefinition(EntityDefinitionConfig $config, EntityDefinitionConfig $configToMerge): void
    {
        $config->setKey($configToMerge->getKey());
        $this->mergeUpsertConfig($config->getUpsertConfig(), $configToMerge->getUpsertConfig());
        $this->mergeEntityConfigAttributes($config, $configToMerge);
        if ($configToMerge->getIdentifierFieldNames()) {
            $config->setIdentifierFieldNames($configToMerge->getIdentifierFieldNames());
        }
        $fieldsToMerge = $configToMerge->getFields();
        foreach ($fieldsToMerge as $fieldName => $fieldToMerge) {
            $field = $config->getField($fieldName);
            if (null !== $field) {
                $this->mergeFieldDefinition($config, $field, $fieldToMerge);
            } else {
                $renamedFieldName = null;
                $propertyPath = $fieldToMerge->getPropertyPath();
                if ($propertyPath && $propertyPath !== $fieldName) {
                    $renamedFieldName = $config->findFieldNameByPropertyPath($propertyPath);
                }
                if (null !== $renamedFieldName) {
                    $field = $config->getField($renamedFieldName);
                    $this->mergeFieldDefinition($config, $field, $fieldToMerge);
                    $config->removeField($renamedFieldName);
                    $config->addField($fieldName, $field);
                } else {
                    $config->addField($fieldName, $fieldToMerge);
                }
            }
        }
    }

    private function mergeFieldDefinition(
        EntityDefinitionConfig $config,
        EntityDefinitionFieldConfig $field,
        EntityDefinitionFieldConfig $fieldToMerge
    ): void {
        $this->mergeFieldConfigAttributes($field, $fieldToMerge);
        $targetEntity = $field->getTargetEntity();
        $targetEntityToMerge = $fieldToMerge->getTargetEntity();
        if (null !== $targetEntity) {
            if (null !== $targetEntityToMerge) {
                $config->setKey(null);
                $this->mergeDefinition($targetEntity, $targetEntityToMerge);
            }
        } elseif (null !== $targetEntityToMerge) {
            $field->setTargetEntity($targetEntityToMerge);
        }
    }

    private function mergeUpsertConfig(UpsertConfig $config, UpsertConfig $configToMerge): void
    {
        if ($configToMerge->hasEnabled()) {
            $config->setEnabled($configToMerge->isEnabled());
        }
        if ($configToMerge->hasAllowedById()) {
            $config->setAllowedById($configToMerge->isAllowedById());
        }
        if ($configToMerge->isReplaceFields()) {
            $config->replaceFields($configToMerge->getFields());
        } elseif ($config->isReplaceFields()) {
            $config->replaceFields(array_merge($config->getFields(), $configToMerge->getFields()));
        } else {
            $fieldsTpMerge = $configToMerge->getFields();
            foreach ($fieldsTpMerge as $fieldNames) {
                $config->addFields($fieldNames);
            }
        }
    }

    private function mergeConfigSection(EntityConfigInterface $config, EntityConfigInterface $configToMerge): void
    {
        $this->mergeEntityConfigAttributes($config, $configToMerge);
        $fieldsToMerge = $configToMerge->getFields();
        foreach ($fieldsToMerge as $fieldName => $fieldToMerge) {
            if ($config->hasField($fieldName)) {
                $this->mergeFieldConfigAttributes($config->getField($fieldName), $fieldToMerge);
            } else {
                $config->addField($fieldName, $fieldToMerge);
            }
        }
    }

    private function mergeEntityConfigAttributes(
        EntityConfigInterface $config,
        EntityConfigInterface $configToMerge
    ): void {
        $keysToMerge = $configToMerge->keys();
        foreach ($keysToMerge as $key) {
            $config->set($key, $configToMerge->get($key));
        }
        if ($configToMerge->hasExclusionPolicy()) {
            $config->setExclusionPolicy($configToMerge->getExclusionPolicy());
        }
    }

    private function mergeFieldConfigAttributes(
        FieldConfigInterface $config,
        FieldConfigInterface $configToMerge
    ): void {
        $keysToMerge = $configToMerge->keys();
        foreach ($keysToMerge as $key) {
            $config->set($key, $configToMerge->get($key));
        }
        if ($configToMerge->hasExcluded()) {
            $config->setExcluded($configToMerge->isExcluded());
        }
    }
}
