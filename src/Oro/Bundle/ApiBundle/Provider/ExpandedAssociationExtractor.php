<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Request\DataType;

class ExpandedAssociationExtractor
{
    /**
     * Returns all associations that have any other fields in addition to identifier fields.
     *
     * @param EntityDefinitionConfig $config
     *
     * @return EntityDefinitionFieldConfig[] [field name => EntityDefinitionFieldConfig, ...]
     */
    public function getExpandedAssociations(EntityDefinitionConfig $config)
    {
        $result = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field->isExcluded() && $this->isExpandedAssociation($field)) {
                $result[$fieldName] = $field;
            }
        }

        return $result;
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     *
     * @return bool
     */
    protected function isExpandedAssociation(EntityDefinitionFieldConfig $field)
    {
        $targetConfig = $field->getTargetEntity();
        if (null === $targetConfig) {
            return false;
        }
        if (DataType::isAssociationAsField($field->getDataType())) {
            return false;
        }
        if (!$field->getTargetClass()) {
            return false;
        }
        $targetIdFieldNames = $targetConfig->getIdentifierFieldNames();
        if (empty($targetIdFieldNames)) {
            return false;
        }

        $hasNotIdentifierFields = false;
        $targetFields = $targetConfig->getFields();
        foreach ($targetFields as $targetFieldName => $targetField) {
            if (!$targetField->isMetaProperty() && !in_array($targetFieldName, $targetIdFieldNames, true)) {
                $hasNotIdentifierFields = true;
                break;
            }
        }

        return $hasNotIdentifierFields;
    }
}
