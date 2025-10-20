<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The helper class to complete the configuration of API resource based on not ORM entity.
 */
class CompleteObjectDefinitionHelper
{
    private CompleteAssociationHelper $associationHelper;

    public function __construct(CompleteAssociationHelper $associationHelper)
    {
        $this->associationHelper = $associationHelper;
    }

    public function completeDefinition(EntityDefinitionConfig $definition, ConfigContext $context): void
    {
        if ($context->hasExtra(FilterIdentifierFieldsConfigExtra::NAME)) {
            $this->removeObjectNonIdentifierFields($definition);
        } else {
            $this->completeObjectAssociations($definition, $context->getVersion(), $context->getRequestType());
        }
        $this->completeEnumOptionHint($context->getClassName(), $definition);
    }

    private function removeObjectNonIdentifierFields(EntityDefinitionConfig $definition): void
    {
        $idFieldNames = $definition->getIdentifierFieldNames();
        $fieldNames = array_keys($definition->getFields());
        foreach ($fieldNames as $fieldName) {
            /** @var EntityDefinitionFieldConfig $field */
            $field = $definition->getField($fieldName);
            if (\in_array($fieldName, $idFieldNames, true)
                || (
                    $field->isMetaProperty()
                    && (
                        ConfigUtil::isRequiredMetaProperty($fieldName)
                        || ConfigUtil::isRequiredMetaProperty($field->getPropertyPath($fieldName))
                    )
                )
            ) {
                continue;
            }
            $definition->removeField($fieldName);
        }
    }

    private function completeObjectAssociations(
        EntityDefinitionConfig $definition,
        string $version,
        RequestType $requestType
    ): void {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $this->completeObjectAssociation($definition, $fieldName, $field, $version, $requestType);
        }
    }

    private function completeObjectAssociation(
        EntityDefinitionConfig $definition,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        string $version,
        RequestType $requestType
    ): void {
        $dataType = $field->getDataType();
        if (DataType::isNestedObject($dataType)) {
            $this->associationHelper->completeNestedObject($fieldName, $field);
        } elseif (DataType::isNestedAssociation($dataType)) {
            $this->associationHelper->completeNestedAssociation($definition, $field, $version, $requestType);
        } else {
            $targetClass = $field->getTargetClass();
            if ($targetClass) {
                $this->associationHelper->completeAssociation($field, $targetClass, $version, $requestType);
            }
        }
    }

    private function completeEnumOptionHint(string $entityClass, EntityDefinitionConfig $definition): void
    {
        if (ExtendHelper::isOutdatedEnumOptionEntity($entityClass)) {
            $hasTranslatableHint = false;
            $hints = $definition->getHints();
            foreach ($hints as $hint) {
                if (\is_array($hint) && 'HINT_ENUM_OPTION' === $hint['name']) {
                    $hasTranslatableHint = true;
                    break;
                }
            }
            if (!$hasTranslatableHint) {
                $definition->addHint('HINT_ENUM_OPTION', ExtendHelper::getEnumCode($entityClass));
            }
        }
    }
}
