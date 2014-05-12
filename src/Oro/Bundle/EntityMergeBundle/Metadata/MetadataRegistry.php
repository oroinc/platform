<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

class MetadataRegistry
{
    /**
     * @var EntityMetadata[]
     */
    protected $loadedMetadata;

    /**
     * @var MetadataBuilder
     */
    protected $metadataBuilder;

    /**
     * @param MetadataBuilder $metadataBuilder
     */
    public function __construct(MetadataBuilder $metadataBuilder)
    {
        $this->metadataBuilder = $metadataBuilder;
    }

    /**
     * Create merge entity metadata
     *
     * @param string $className
     * @return EntityMetadata
     */
    public function getEntityMetadata($className)
    {
        if (isset($this->loadedMetadata[$className])) {
            return $this->loadedMetadata[$className];
        }

        return $this->loadedMetadata[$className] = $this->metadataBuilder->createEntityMetadataByClass($className);
    }
}
