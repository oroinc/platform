<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\PropertyMetadata;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Provides a set of methods to help loading of Data API metadata.
 */
class MetadataHelper
{
    /**
     * @param mixed  $dataType
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return mixed
     */
    public function assertDataType($dataType, $entityClass, $fieldName)
    {
        if (!$dataType) {
            throw new RuntimeException(\sprintf(
                'The "%s" configuration attribute should be specified for the "%s" field of the "%s" entity.',
                ConfigUtil::DATA_TYPE,
                $fieldName,
                $entityClass
            ));
        }

        return $dataType;
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     *
     * @return string|null
     */
    public function getFormPropertyPath(EntityDefinitionFieldConfig $field, $targetAction)
    {
        $propertyPath = null;
        if (\in_array($targetAction, [ApiActions::CREATE, ApiActions::UPDATE], true)) {
            $formOptions = $field->getFormOptions();
            if (!empty($formOptions) && \array_key_exists('property_path', $formOptions)) {
                $propertyPath = $formOptions['property_path'];
            }
        }

        return $propertyPath;
    }

    /**
     * @param PropertyMetadata            $propertyMetadata
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     */
    public function setPropertyPath(
        PropertyMetadata $propertyMetadata,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $targetAction
    ) {
        $propertyPath = $this->getFormPropertyPath($field, $targetAction);
        if (!$propertyPath) {
            $propertyPath = $field->getPropertyPath($fieldName);
        }
        if ($propertyPath !== $fieldName) {
            $propertyMetadata->setPropertyPath($propertyPath);
        }
    }
}
