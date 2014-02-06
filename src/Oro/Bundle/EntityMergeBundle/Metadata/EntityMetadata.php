<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

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
    public function __construct($options = [], $fieldMetadata = [])
    {
        if (!is_array($options)) {
            throw new InvalidArgumentException('Options argument should have array type');
        }

        if (!is_array($fieldMetadata)) {
            throw new InvalidArgumentException('FieldMetadata argument should have array type');
        }

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
            $this->get(DoctrineMetadata::OPTION_NAME)->get('name') : null;
    }
}
