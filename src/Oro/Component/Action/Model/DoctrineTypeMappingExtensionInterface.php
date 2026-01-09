<?php

namespace Oro\Component\Action\Model;

/**
 * Defines the contract for mapping Doctrine database types to action attribute types.
 *
 * Implementations of this interface manage the mapping between Doctrine ORM column types
 * and action framework attribute types, enabling proper type conversion and validation
 * when working with entity properties in actions.
 */
interface DoctrineTypeMappingExtensionInterface
{
    /**
     * @param string $doctrineType
     * @param string $attributeType
     * @param array $attributeOptions
     */
    public function addDoctrineTypeMapping($doctrineType, $attributeType, array $attributeOptions = []);

    /**
     * @return array
     */
    public function getDoctrineTypeMappings();
}
