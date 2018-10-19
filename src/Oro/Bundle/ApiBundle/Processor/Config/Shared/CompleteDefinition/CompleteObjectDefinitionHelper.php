<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * The helper class to complete the configuration of Data API resource based on not ORM entity.
 */
class CompleteObjectDefinitionHelper
{
    /** @var CompleteAssociationHelper */
    private $associationHelper;

    /**
     * @param CompleteAssociationHelper $associationHelper
     */
    public function __construct(CompleteAssociationHelper $associationHelper)
    {
        $this->associationHelper = $associationHelper;
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param ConfigContext          $context
     */
    public function completeDefinition(EntityDefinitionConfig $definition, ConfigContext $context)
    {
        if ($context->hasExtra(FilterIdentifierFieldsConfigExtra::NAME)) {
            $this->removeObjectNonIdentifierFields($definition);
        } else {
            $this->completeObjectAssociations($definition, $context->getVersion(), $context->getRequestType());
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     */
    private function removeObjectNonIdentifierFields(EntityDefinitionConfig $definition)
    {
        $idFieldNames = $definition->getIdentifierFieldNames();
        $fieldNames = array_keys($definition->getFields());
        foreach ($fieldNames as $fieldName) {
            if (!in_array($fieldName, $idFieldNames, true)
                && !$definition->getField($fieldName)->isMetaProperty()
            ) {
                $definition->removeField($fieldName);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $version
     * @param RequestType            $requestType
     */
    private function completeObjectAssociations(
        EntityDefinitionConfig $definition,
        $version,
        RequestType $requestType
    ) {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $this->completeObjectAssociation($definition, $fieldName, $field, $version, $requestType);
        }
    }

    /**
     * @param string                      $fieldName
     * @param EntityDefinitionConfig      $definition
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $version
     * @param RequestType                 $requestType
     */
    private function completeObjectAssociation(
        EntityDefinitionConfig $definition,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $version,
        RequestType $requestType
    ) {
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
}
