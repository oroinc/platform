<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition;

use Oro\Bundle\ApiBundle\Config\ConfigBagInterface;
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
 */
class CompleteAssociationHelper
{
    /** @var ConfigProvider */
    private $configProvider;

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
        $targetClass,
        $version,
        RequestType $requestType,
        array $extras = []
    ) {
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
            $this->mergeAttribute($targetEntity, $targetDefinition, ConfigUtil::PARENT_RESOURCE_CLASS);
            if (!$targetEntity->isExcludeAll()) {
                $this->mergeTargetEntityConfig($targetEntity, $targetDefinition);
                $targetEntity->setExcludeAll();
                if (!$field->hasCollapsed()) {
                    $field->setCollapsed();
                }
            }
        }
    }

    /**
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     */
    public function completeNestedObject($fieldName, EntityDefinitionFieldConfig $field)
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

    /**
     * @param EntityDefinitionConfig      $definition
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $version
     * @param RequestType                 $requestType
     */
    public function completeNestedAssociation(
        EntityDefinitionConfig $definition,
        EntityDefinitionFieldConfig $field,
        $version,
        RequestType $requestType
    ) {
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

    /**
     * @param bool $isCollection
     *
     * @return string
     */
    public function getAssociationTargetType($isCollection)
    {
        return ConfigUtil::getAssociationTargetType($isCollection);
    }

    /**
     * @param string                 $entityClass
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     *
     * @return EntityDefinitionConfig|null
     */
    public function loadDefinition($entityClass, $version, RequestType $requestType, array $extras = [])
    {
        return $this->configProvider
            ->getConfig(
                $entityClass,
                $version,
                $requestType,
                \array_merge($extras, [new EntityDefinitionConfigExtra()])
            )
            ->getDefinition();
    }

    /**
     * @param ConfigExtraInterface[] $extras
     *
     * @return ExpandRelatedEntitiesConfigExtra|null
     */
    private function getExpandRelatedEntitiesConfigExtra(array $extras)
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
    ) {
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
    ) {
        $this->mergeTargetEntityFieldConfigAttributes($field, $fieldToMerge);
        $targetEntity = $field->getTargetEntity();
        $targetEntityToMerge = $fieldToMerge->getTargetEntity();
        if (null !== $targetEntity) {
            if (null !== $targetEntityToMerge) {
                if (!$targetEntity->getIdentifierFieldNames()) {
                    $targetEntity->setIdentifierFieldNames($targetEntityToMerge->getIdentifierFieldNames());
                }
                $this->mergeAttribute($targetEntity, $targetEntityToMerge, ConfigUtil::PARENT_RESOURCE_CLASS);
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
    ) {
        if (!$config->hasExclusionPolicy() && $configToMerge->hasExclusionPolicy()) {
            $config->setExclusionPolicy($configToMerge->getExclusionPolicy());
        }
        if (!$config->getIdentifierFieldNames()) {
            $config->setIdentifierFieldNames($configToMerge->getIdentifierFieldNames());
        }
        $this->mergeAttribute($config, $configToMerge, ConfigUtil::ORDER_BY);
        $this->mergeAttribute($config, $configToMerge, ConfigUtil::MAX_RESULTS);
        $this->mergeAttribute($config, $configToMerge, ConfigUtil::HINTS);
    }

    private function mergeTargetEntityFieldConfigAttributes(
        EntityDefinitionFieldConfig $field,
        EntityDefinitionFieldConfig $fieldToMerge
    ) {
        if (!$field->hasExcluded() && $fieldToMerge->hasExcluded()) {
            $field->setExcluded($fieldToMerge->isExcluded());
        }
        if (!$field->hasDataType() && $fieldToMerge->hasDataType()) {
            $field->setDataType($fieldToMerge->getDataType());
        }
        $this->mergeAttribute($field, $fieldToMerge, ConfigUtil::COLLAPSE);
        $this->mergeAttribute($field, $fieldToMerge, ConfigUtil::PROPERTY_PATH);
        $this->mergeAttribute($field, $fieldToMerge, ConfigUtil::DATA_TRANSFORMER);
        $this->mergeAttribute($field, $fieldToMerge, ConfigUtil::POST_PROCESSOR);
        $this->mergeAttribute($field, $fieldToMerge, ConfigUtil::POST_PROCESSOR_OPTIONS);
        $this->mergeAttribute($field, $fieldToMerge, ConfigUtil::TARGET_CLASS);
        $this->mergeAttribute($field, $fieldToMerge, ConfigUtil::TARGET_TYPE);
        $this->mergeAttribute($field, $fieldToMerge, ConfigUtil::DEPENDS_ON);
        $this->mergeAttribute($field, $fieldToMerge, ConfigUtil::META_PROPERTY);
        $this->mergeAttribute($field, $fieldToMerge, ConfigUtil::META_PROPERTY_RESULT_NAME);
        $this->mergeAttribute($field, $fieldToMerge, ConfigUtil::FORM_TYPE);
        $this->mergeAttribute($field, $fieldToMerge, ConfigUtil::FORM_OPTIONS);
    }

    private function mergeAttribute(
        ConfigBagInterface $config,
        ConfigBagInterface $configToMerge,
        string $attributeName
    ) {
        if ($configToMerge->has($attributeName) && !$config->has($attributeName)) {
            $config->set($attributeName, $configToMerge->get($attributeName));
        }
    }

    private function completeDependsOn(EntityDefinitionFieldConfig $field)
    {
        $targetFields = $field->getTargetEntity()->getFields();
        foreach ($targetFields as $targetFieldName => $targetField) {
            $field->addDependsOn($targetField->getPropertyPath($targetFieldName));
        }
    }
}
