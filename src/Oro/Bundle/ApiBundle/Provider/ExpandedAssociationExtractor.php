<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\AssociationConfigUtil;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * A helper class to extractor information about expanded associations.
 */
class ExpandedAssociationExtractor
{
    /**
     * Returns all associations that were requested to expand
     * and have any other fields in addition to identifier fields.
     *
     * @param EntityDefinitionConfig           $config
     * @param ExpandRelatedEntitiesConfigExtra $expandConfigExtra
     * @param string|null                      $associationPath
     *
     * @return EntityDefinitionFieldConfig[] [field name => EntityDefinitionFieldConfig, ...]
     */
    public function getExpandedAssociations(
        EntityDefinitionConfig $config,
        ExpandRelatedEntitiesConfigExtra $expandConfigExtra,
        string $associationPath = null
    ): array {
        $result = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }

            $fieldPath = $associationPath
                ? $associationPath . ConfigUtil::PATH_DELIMITER . $fieldName
                : $fieldName;
            if (!$expandConfigExtra->isExpandRequested($fieldPath)) {
                continue;
            }

            $association = AssociationConfigUtil::getAssociationConfig($field, $config);
            if (null !== $association && $this->isExpandedAssociation($association)) {
                $result[$fieldName] = $association;
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
        foreach ($pathsToExpand as $path) {
            $firstDelimiter = strpos($path, ConfigUtil::PATH_DELIMITER);
            if (false !== $firstDelimiter) {
                $fieldName = substr($path, 0, $firstDelimiter);
                $resolvedPath = $this->resolveFirstLevelOfExpandedAssociation($fieldName, $config);
                if ($resolvedPath) {
                    $resolvedPathFirstDelimiter = strpos($resolvedPath, ConfigUtil::PATH_DELIMITER);
                    if (false !== $resolvedPathFirstDelimiter) {
                        $fieldName = substr($resolvedPath, 0, $resolvedPathFirstDelimiter);
                        $path = $resolvedPath . substr($path, $firstDelimiter);
                        $firstDelimiter = $resolvedPathFirstDelimiter;
                    }
                }
                $result[$fieldName][] = substr($path, $firstDelimiter + 1);
            } else {
                $resolvedPath = $this->resolveFirstLevelOfExpandedAssociation($path, $config);
                if ($resolvedPath) {
                    $firstDelimiter = strpos($resolvedPath, ConfigUtil::PATH_DELIMITER);
                    if (false !== $firstDelimiter) {
                        $fieldName = substr($resolvedPath, 0, $firstDelimiter);
                        $result[$fieldName][] = substr($resolvedPath, $firstDelimiter + 1);
                    }
                }
            }
        }

        return $result;
    }

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

    private function isExpandedAssociation(EntityDefinitionFieldConfig $field): bool
    {
        $targetConfig = $field->getTargetEntity();
        if (null === $targetConfig) {
            return false;
        }
        if (DataType::isAssociationAsField($field->getDataType())) {
            return false;
        }
        $targetClass = $field->getTargetClass();
        if (!$targetClass) {
            return false;
        }

        return
            is_a($targetClass, EntityIdentifier::class, true)
            || $this->hasNotIdentifierFields($targetConfig);
    }

    private function hasNotIdentifierFields(EntityDefinitionConfig $config): bool
    {
        $idFieldNames = $config->getIdentifierFieldNames();
        if (!$idFieldNames) {
            return false;
        }

        $hasNotIdentifierFields = false;
        $targetFields = $config->getFields();
        foreach ($targetFields as $targetFieldName => $targetField) {
            if (!$targetField->isMetaProperty() && !\in_array($targetFieldName, $idFieldNames, true)) {
                $hasNotIdentifierFields = true;
                break;
            }
        }

        return $hasNotIdentifierFields;
    }
}
