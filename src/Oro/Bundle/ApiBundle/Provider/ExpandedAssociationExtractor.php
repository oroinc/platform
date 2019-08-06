<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * A helper class to extractor information about expanded associations.
 */
class ExpandedAssociationExtractor
{
    /**
     * Returns all associations that have any other fields in addition to identifier fields.
     *
     * @param EntityDefinitionConfig $config
     *
     * @return EntityDefinitionFieldConfig[] [field name => EntityDefinitionFieldConfig, ...]
     */
    public function getExpandedAssociations(EntityDefinitionConfig $config): array
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
    public function getFirstLevelOfExpandedAssociations(EntityDefinitionConfig $config, array $pathsToExpand): array
    {
        $result = [];
        if (!empty($pathsToExpand)) {
            foreach ($pathsToExpand as $path) {
                $firstDelimiter = \strpos($path, ConfigUtil::PATH_DELIMITER);
                if (false !== $firstDelimiter) {
                    $fieldName = \substr($path, 0, $firstDelimiter);
                    $resolvedPath = $this->resolveFirstLevelOfExpandedAssociation($fieldName, $config);
                    if ($resolvedPath) {
                        $resolvedPathFirstDelimiter = \strpos($resolvedPath, ConfigUtil::PATH_DELIMITER);
                        if (false !== $resolvedPathFirstDelimiter) {
                            $fieldName = \substr($resolvedPath, 0, $resolvedPathFirstDelimiter);
                            $path = $resolvedPath . \substr($path, $firstDelimiter);
                            $firstDelimiter = $resolvedPathFirstDelimiter;
                        }
                    }
                    $result[$fieldName][] = \substr($path, $firstDelimiter + 1);
                } else {
                    $resolvedPath = $this->resolveFirstLevelOfExpandedAssociation($path, $config);
                    if ($resolvedPath) {
                        $firstDelimiter = \strpos($resolvedPath, ConfigUtil::PATH_DELIMITER);
                        if (false !== $firstDelimiter) {
                            $fieldName = \substr($resolvedPath, 0, $firstDelimiter);
                            $result[$fieldName][] = \substr($resolvedPath, $firstDelimiter + 1);
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param string                 $fieldName
     * @param EntityDefinitionConfig $config
     *
     * @return string|null
     */
    private function resolveFirstLevelOfExpandedAssociation(
        string $fieldName,
        EntityDefinitionConfig $config
    ): ?string {
        $field = $config->getField($fieldName);
        if (null === $field) {
            return null;
        }

        $propertyPath = $field->getPropertyPath();
        if (!$propertyPath) {
            return null;
        }

        return $propertyPath;
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     *
     * @return bool
     */
    private function isExpandedAssociation(EntityDefinitionFieldConfig $field): bool
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
            if (!$targetField->isMetaProperty() && !\in_array($targetFieldName, $targetIdFieldNames, true)) {
                $hasNotIdentifierFields = true;
                break;
            }
        }

        return $hasNotIdentifierFields;
    }
}
