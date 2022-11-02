<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Represents a service to complete definition of a field with a custom data-type.
 */
interface CustomDataTypeCompleterInterface
{
    /**
     * Checks the data-type of the given field and if it is supported by this completer,
     * completes the definition of the field.
     *
     * @param ClassMetadata               $metadata
     * @param EntityDefinitionConfig      $definition
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $dataType
     * @param string                      $version
     * @param RequestType                 $requestType
     *
     * @return bool TRUE if the given field was completed by this completer; otherwise, FALSE
     */
    public function completeCustomDataType(
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        string $dataType,
        string $version,
        RequestType $requestType
    ): bool;
}
