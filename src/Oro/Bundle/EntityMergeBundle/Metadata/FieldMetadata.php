<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

class FieldMetadata extends Metadata implements MetadataInterface, FieldMetadataInterface
{
    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var array
     */
    protected $mapping;

    /**
     * @param array $options
     * @param array $mapping
     */
    public function __construct(array $options, array $mapping)
    {
        $this->options = $options;
        $this->mapping = $mapping;
    }

    /**
     * {inheritDoc}
     */
    public function getFieldName()
    {
        return null;
    }
}
