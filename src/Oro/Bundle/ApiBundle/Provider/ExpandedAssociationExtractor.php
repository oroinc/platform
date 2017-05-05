<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

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
     * Returns an array contains all fields requested to be expanded.
     *
     * @param EntityDefinitionConfig $config
     * @param string[]               $pathsToExpand [path, ...]
     *
     * @return array [field name => [path, ...], ...]
     */
    public function getFirstLevelOfExpandedAssociations(EntityDefinitionConfig $config, array $pathsToExpand)
    {
        $result = [];
        if (!empty($pathsToExpand)) {
            foreach ($pathsToExpand as $path) {
                $firstDelimiter = strpos($path, ConfigUtil::PATH_DELIMITER);
                if (false !== $firstDelimiter) {
                    $result[substr($path, 0, $firstDelimiter)][] = substr($path, $firstDelimiter + 1);
                    continue;
                }

                $field = $config->getField($path);
                if (null === $field) {
                    continue;
                }

                $propertyPath = $field->getPropertyPath();
                if (!$propertyPath) {
                    continue;
                }

                $firstDelimiter = strpos($propertyPath, ConfigUtil::PATH_DELIMITER);
                if (false === $firstDelimiter) {
                    continue;
                }

                $result[substr($propertyPath, 0, $firstDelimiter)][] = substr($propertyPath, $firstDelimiter + 1);
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
