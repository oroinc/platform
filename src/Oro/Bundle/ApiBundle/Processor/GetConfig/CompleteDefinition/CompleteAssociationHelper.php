<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The helper class to complete the configuration of different kind of ORM associations.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CompleteAssociationHelper
{
    private ConfigProvider $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetClass
     * @param string                      $version
     * @param RequestType                 $requestType
     * @param ConfigExtraInterface[]      $extras
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function completeAssociation(
        EntityDefinitionFieldConfig $field,
        string $targetClass,
        string $version,
        RequestType $requestType,
        array $extras = []
    ): void {
        $expandRelatedEntitiesExtra = $this->getExpandRelatedEntitiesConfigExtra($extras);
        if (null !== $expandRelatedEntitiesExtra) {
            $extras[] = new FilterFieldsConfigExtra([
                $targetClass => $expandRelatedEntitiesExtra->getExpandedEntities()
            ]);
        } elseif (!$field->hasCollapsed() || $field->isCollapsed()) {
            $extras[] = new FilterIdentifierFieldsConfigExtra();
        }

        $targetDefinition = $this->loadDefinition($targetClass, $version, $requestType, $extras);
        if (null !== $targetDefinition) {
            if (!$field->getTargetClass()) {
                $field->setTargetClass($targetClass);
            }

            $targetEntity = $field->getTargetEntity();
            if (null === $targetEntity) {
                $targetEntity = $field->createAndSetTargetEntity();
            } elseif (!$targetEntity->getIdentifierFieldNames()) {
                $targetEntity->setIdentifierFieldNames($targetDefinition->getIdentifierFieldNames());
            }
            $this->mergeEntityConfigAttributes($targetEntity, $targetDefinition, ConfigUtil::PARENT_RESOURCE_CLASS);
            if (!$targetEntity->isExcludeAll()) {
                $this->mergeTargetEntityConfig($targetEntity, $targetDefinition);
                $targetEntity->setExcludeAll();
                if (!$field->hasCollapsed()) {
                    $field->setCollapsed();
                }
            }
        }
    }

    public function completeNestedObject(string $fieldName, EntityDefinitionFieldConfig $field): void
    {
        $field->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $field->getOrCreateTargetEntity()->setExcludeAll();

        $formOptions = $field->getFormOptions();
        $inheritData = $formOptions['inherit_data'] ?? false;
        if (!$inheritData && (null === $formOptions || !\array_key_exists('property_path', $formOptions))) {
            $formOptions['property_path'] = $fieldName;
            $field->setFormOptions($formOptions);
        }

        $this->completeDependsOn($field);
    }

    public function completeNestedAssociation(
        EntityDefinitionConfig $definition,
        EntityDefinitionFieldConfig $field,
        string $version,
        RequestType $requestType
    ): void {
        $field->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $this->completeAssociation($field, EntityIdentifier::class, $version, $requestType);
        $this->completeDependsOn($field);
        // exclude source fields
        $targetFields = $field->getTargetEntity()->getFields();
        foreach ($targetFields as $targetField) {
            $targetPropertyPath = $targetField->getPropertyPath();
            if ($targetPropertyPath && ConfigUtil::IGNORE_PROPERTY_PATH !== $targetPropertyPath) {
                $sourceField = $definition->findField($targetPropertyPath, true);
                if (null === $sourceField) {
                    $sourceField = $definition->addField($targetPropertyPath);
                }
                $sourceField->setExcluded();
            }
        }
    }

    public function getAssociationTargetType(bool $isCollection): string
    {
        return ConfigUtil::getAssociationTargetType($isCollection);
    }

    public function loadDefinition(
        string $entityClass,
        string $version,
        RequestType $requestType,
        array $extras = []
    ): ?EntityDefinitionConfig {
        return $this->configProvider
            ->getConfig(
                $entityClass,
                $version,
                $requestType,
                array_merge($extras, [new EntityDefinitionConfigExtra()])
            )
            ->getDefinition();
    }

    private function getExpandRelatedEntitiesConfigExtra(array $extras): ?ExpandRelatedEntitiesConfigExtra
    {
        foreach ($extras as $extra) {
            if ($extra instanceof ExpandRelatedEntitiesConfigExtra) {
                return $extra;
            }
        }

        return null;
    }

    private function mergeTargetEntityConfig(
        EntityDefinitionConfig $config,
        EntityDefinitionConfig $configToMerge
    ): void {
        $this->mergeTargetEntityConfigAttributes($config, $configToMerge);
        $fieldsToMerge = $configToMerge->getFields();
        foreach ($fieldsToMerge as $fieldName => $fieldToMerge) {
            $field = $config->getField($fieldName);
            if (null !== $field) {
                $this->mergeTargetEntityFieldConfig($field, $fieldToMerge);
            } else {
                $config->addField($fieldName, $fieldToMerge);
            }
        }
    }

    private function mergeTargetEntityFieldConfig(
        EntityDefinitionFieldConfig $field,
        EntityDefinitionFieldConfig $fieldToMerge
    ): void {
        $this->mergeTargetEntityFieldConfigAttributes($field, $fieldToMerge);
        $targetEntity = $field->getTargetEntity();
        $targetEntityToMerge = $fieldToMerge->getTargetEntity();
        if (null !== $targetEntity) {
            if (null !== $targetEntityToMerge) {
                if (!$targetEntity->getIdentifierFieldNames()) {
                    $targetEntity->setIdentifierFieldNames($targetEntityToMerge->getIdentifierFieldNames());
                }
                $this->mergeEntityConfigAttributes(
                    $targetEntity,
                    $targetEntityToMerge,
                    ConfigUtil::PARENT_RESOURCE_CLASS
                );
                if (!$targetEntity->isExcludeAll()) {
                    $this->mergeTargetEntityConfig($targetEntity, $targetEntityToMerge);
                }
            }
        } elseif (null !== $targetEntityToMerge) {
            $field->setTargetEntity($targetEntityToMerge);
        }
    }

    private function mergeTargetEntityConfigAttributes(
        EntityDefinitionConfig $config,
        EntityDefinitionConfig $configToMerge
    ): void {
        if (!$config->hasExclusionPolicy() && $configToMerge->hasExclusionPolicy()) {
            $config->setExclusionPolicy($configToMerge->getExclusionPolicy());
        }
        if (!$config->getIdentifierFieldNames()) {
            $config->setIdentifierFieldNames($configToMerge->getIdentifierFieldNames());
        }
        $this->mergeEntityConfigAttributes($config, $configToMerge, ConfigUtil::ORDER_BY);
        $this->mergeEntityConfigAttributes($config, $configToMerge, ConfigUtil::MAX_RESULTS);
        $this->mergeEntityConfigAttributes($config, $configToMerge, ConfigUtil::HINTS);
    }

    private function mergeTargetEntityFieldConfigAttributes(
        EntityDefinitionFieldConfig $field,
        EntityDefinitionFieldConfig $fieldToMerge
    ): void {
        if (!$field->hasExcluded() && $fieldToMerge->hasExcluded()) {
            $field->setExcluded($fieldToMerge->isExcluded());
        }
        if (!$field->hasDataType() && $fieldToMerge->hasDataType()) {
            $field->setDataType($fieldToMerge->getDataType());
        }
        $this->mergeFieldConfigAttributes($field, $fieldToMerge, ConfigUtil::COLLAPSE);
        $this->mergeFieldConfigAttributes($field, $fieldToMerge, ConfigUtil::PROPERTY_PATH);
        $this->mergeFieldConfigAttributes($field, $fieldToMerge, ConfigUtil::DATA_TRANSFORMER);
        $this->mergeFieldConfigAttributes($field, $fieldToMerge, ConfigUtil::POST_PROCESSOR);
        $this->mergeFieldConfigAttributes($field, $fieldToMerge, ConfigUtil::POST_PROCESSOR_OPTIONS);
        $this->mergeFieldConfigAttributes($field, $fieldToMerge, ConfigUtil::TARGET_CLASS);
        $this->mergeFieldConfigAttributes($field, $fieldToMerge, ConfigUtil::TARGET_TYPE);
        $this->mergeFieldConfigAttributes($field, $fieldToMerge, ConfigUtil::DEPENDS_ON);
        $this->mergeFieldConfigAttributes($field, $fieldToMerge, ConfigUtil::META_PROPERTY);
        $this->mergeFieldConfigAttributes($field, $fieldToMerge, ConfigUtil::META_PROPERTY_RESULT_NAME);
        $this->mergeFieldConfigAttributes($field, $fieldToMerge, ConfigUtil::FORM_TYPE);
        $this->mergeFieldConfigAttributes($field, $fieldToMerge, ConfigUtil::FORM_OPTIONS);
    }

    private function mergeEntityConfigAttributes(
        EntityDefinitionConfig $config,
        EntityDefinitionConfig $configToMerge,
        string $attributeName
    ): void {
        if ($configToMerge->has($attributeName) && !$config->has($attributeName)) {
            $config->set($attributeName, $configToMerge->get($attributeName));
        }
    }

    private function mergeFieldConfigAttributes(
        EntityDefinitionFieldConfig $config,
        EntityDefinitionFieldConfig $configToMerge,
        string $attributeName
    ): void {
        if ($configToMerge->has($attributeName) && !$config->has($attributeName)) {
            $config->set($attributeName, $configToMerge->get($attributeName));
        }
    }

    private function completeDependsOn(EntityDefinitionFieldConfig $field): void
    {
        $targetFields = $field->getTargetEntity()->getFields();
        foreach ($targetFields as $targetFieldName => $targetField) {
            $field->addDependsOn($targetField->getPropertyPath($targetFieldName));
        }
    }
}
