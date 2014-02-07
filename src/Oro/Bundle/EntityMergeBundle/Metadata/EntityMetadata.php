<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

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
        /** @todo Some checks that DoctrineMetadata::OPTION_NAME exist */
        return $this->has(DoctrineMetadata::OPTION_NAME) ?
            $this->get(DoctrineMetadata::OPTION_NAME)->get('name') : null;
    }
}
