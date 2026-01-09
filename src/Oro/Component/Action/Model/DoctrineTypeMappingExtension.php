<?php

namespace Oro\Component\Action\Model;

/**
 * Manages mappings between Doctrine database types and action attribute types.
 *
 * This extension maintains a registry of type mappings that define how Doctrine ORM column types
 * should be converted to action framework attribute types. It allows registering custom type mappings
 * with associated options for proper type handling and validation.
 */
class DoctrineTypeMappingExtension implements DoctrineTypeMappingExtensionInterface
{
    /** @var array */
    protected $doctrineTypeMappings = [];

    #[\Override]
    public function addDoctrineTypeMapping($doctrineType, $attributeType, array $attributeOptions = [])
    {
        $this->doctrineTypeMappings[$doctrineType] = [
            'type' => $attributeType,
            'options' => $attributeOptions
        ];
    }

    #[\Override]
    public function getDoctrineTypeMappings()
    {
        return $this->doctrineTypeMappings;
    }
}
