<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition;

use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
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

    /**
     * @param ConfigProvider $configProvider
     */
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
     */
    public function completeAssociation(
        EntityDefinitionFieldConfig $field,
        $targetClass,
        $version,
        RequestType $requestType,
        array $extras = []
    ) {
        $expandRelatedEntitiesExtra = null;
        foreach ($extras as $extra) {
            if ($extra instanceof ExpandRelatedEntitiesConfigExtra) {
                $expandRelatedEntitiesExtra = $extra;
                break;
            }
        }

        if (null !== $expandRelatedEntitiesExtra) {
            $extras[] = new FilterFieldsConfigExtra(
                [$targetClass => $expandRelatedEntitiesExtra->getExpandedEntities()]
            );
        } else {
            $extras[] = new FilterIdentifierFieldsConfigExtra();
        }

        $targetDefinition = $this->loadDefinition($targetClass, $version, $requestType, $extras);
        if (null !== $targetDefinition) {
            if (!$field->getTargetClass()) {
                $field->setTargetClass($targetClass);
            }

            $targetEntity = $field->getTargetEntity();
            $isExcludeAll = null !== $targetEntity && $targetEntity->isExcludeAll();
            if (!$targetEntity) {
                $targetEntity = $field->createAndSetTargetEntity();
            }

            $targetEntity->setParentResourceClass($targetDefinition->getParentResourceClass());
            $targetEntity->setIdentifierFieldNames($targetDefinition->getIdentifierFieldNames());

            if (!$isExcludeAll) {
                $targetEntity->setExcludeAll();
                $field->setCollapsed();
                $this->mergeAssociationFields($field, $targetDefinition);
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

        $target = $field->getOrCreateTargetEntity();
        $target->setExcludeAll();

        $formOptions = $field->getFormOptions();
        if (null === $formOptions || !array_key_exists('property_path', $formOptions)) {
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
        return $isCollection ? 'to-many' : 'to-one';
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
        $config = $this->configProvider->getConfig(
            $entityClass,
            $version,
            $requestType,
            array_merge($extras, [new EntityDefinitionConfigExtra()])
        );

        $definition = null;
        if ($config->hasDefinition()) {
            $definition = $config->getDefinition();
        }

        return $definition;
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param EntityDefinitionConfig      $targetDefinition
     */
    public function mergeAssociationFields(
        EntityDefinitionFieldConfig $field,
        EntityDefinitionConfig $targetDefinition
    ) {
        $targetEntity = $field->getTargetEntity();
        $targetFields = $targetDefinition->getFields();
        foreach ($targetFields as $targetFieldName => $targetField) {
            if ($targetEntity->hasField($targetFieldName)) {
                $existingField = $targetEntity->getField($targetFieldName);
                if ($targetField->isMetaProperty()) {
                    $existingField->setMetaProperty(true);
                }
            } else {
                $targetEntity->addField($targetFieldName, $targetField);
            }
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     */
    private function completeDependsOn(EntityDefinitionFieldConfig $field)
    {
        $dependsOn = $field->getDependsOn();
        if (null === $dependsOn) {
            $dependsOn = [];
        }
        $targetFields = $field->getTargetEntity()->getFields();
        foreach ($targetFields as $targetFieldName => $targetField) {
            $targetPropertyPath = $targetField->getPropertyPath($targetFieldName);
            if (!in_array($targetPropertyPath, $dependsOn, true)) {
                $dependsOn[] = $targetPropertyPath;
            }
        }
        $field->setDependsOn($dependsOn);
    }
}
