<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class EntityMetadata extends Metadata implements MetadataInterface, EntityMetadataInterface
{
    /**
     * @var FieldMetadata[]
     */
    protected $fieldsMetadata;

    /**
     * @param array $options
     * @param FieldMetadata[] $fieldsMetadata
     */
    public function __construct(array $options = [], array $fieldsMetadata = [])
    {
        $this->options       = $options;
        $this->fieldsMetadata = $fieldsMetadata;
    }

    /**
     * @return FieldMetadata[]
     */
    public function getFieldsMetadata()
    {
        return $this->fieldsMetadata;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        if (!$this->getDoctrineMetadata()->has('name')) {
            throw new InvalidArgumentException('Class name not set');
        }

        return $this->getDoctrineMetadata()->get('name');
    }
}
