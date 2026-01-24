<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

/**
 * Registry for caching and retrieving entity merge metadata.
 *
 * This class manages the caching of {@see EntityMetadata} objects for entity classes, using
 * a {@see MetadataBuilder} to construct metadata on-demand for classes that have not yet been
 * loaded. It provides a single point of access for retrieving merge metadata throughout
 * the merge process, improving performance by avoiding redundant metadata construction.
 */
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
