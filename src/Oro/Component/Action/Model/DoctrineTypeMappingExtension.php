<?php

namespace Oro\Component\Action\Model;

class DoctrineTypeMappingExtension implements DoctrineTypeMappingExtensionInterface
{
    /** @var array */
    protected $doctrineTypeMappings = [];

    /**
     * {@inheritdoc}
     */
    public function addDoctrineTypeMapping($doctrineType, $attributeType, array $attributeOptions = [])
    {
        $this->doctrineTypeMappings[$doctrineType] = [
            'type' => $attributeType,
            'options' => $attributeOptions
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineTypeMappings()
    {
        return $this->doctrineTypeMappings;
    }
}
