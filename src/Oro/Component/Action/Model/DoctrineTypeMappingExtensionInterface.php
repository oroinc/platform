<?php

namespace Oro\Component\Action\Model;

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
