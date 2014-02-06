<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

class EntityMetadata extends Metadata implements MetadataInterface, EntityMetadataInterface
{
    /**
     * @var CollectionMetadata[]|FieldMetadata[]
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

    /**
     * @return Metadata[]
     */
    public function getClassName()
    {
        return $this->has(DoctrineMetadata::OPTION_NAME) ?
            $this->get(DoctrineMetadata::OPTION_NAME)->get('name') : false;
    }
}
