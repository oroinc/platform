<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

class EntityMetadata extends Metadata implements MetadataInterface
{
    /**
     * @var Metadata[]
     */
    protected $fieldMetadata;

    /**
     * @param array $options
     * @param array $fieldMetadata
     */
    public function __construct(array $options, array $fieldMetadata)
    {
        $this->options       = $options;
        $this->fieldMetadata = $fieldMetadata;
    }

    /**
     * @return Metadata[]
     */
    public function getFieldMetadata()
    {
        return $this->fieldMetadata;
    }
}
