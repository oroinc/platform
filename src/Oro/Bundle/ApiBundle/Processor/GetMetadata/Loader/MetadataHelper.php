<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\PropertyMetadata;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Provides a set of methods to help loading of API metadata.
 */
class MetadataHelper
{
    public function assertDataType(?string $dataType, string $entityClass, string $fieldName): string
    {
        if (!$dataType) {
            throw new RuntimeException(sprintf(
                'The "%s" configuration attribute should be specified for the "%s" field of the "%s" entity.',
                ConfigUtil::DATA_TYPE,
                $fieldName,
                $entityClass
            ));
        }

        return $dataType;
    }

    public function getFormPropertyPath(EntityDefinitionFieldConfig $field, ?string $targetAction): ?string
    {
        $propertyPath = null;
        if ($targetAction && \in_array($targetAction, [ApiAction::CREATE, ApiAction::UPDATE], true)) {
            $formOptions = $field->getFormOptions();
            if (!empty($formOptions) && \array_key_exists('property_path', $formOptions)) {
                $propertyPath = $formOptions['property_path'];
            }
        }

        return $propertyPath;
    }

    public function setPropertyPath(
        PropertyMetadata $propertyMetadata,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        ?string $targetAction
    ): void {
        $propertyPath = $this->getFormPropertyPath($field, $targetAction);
        if (!$propertyPath) {
            $propertyPath = $field->getPropertyPath($fieldName);
        }
        if ($propertyPath !== $fieldName) {
            $propertyMetadata->setPropertyPath($propertyPath);
        }
    }
}
