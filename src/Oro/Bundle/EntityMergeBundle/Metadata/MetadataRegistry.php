<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

class MetadataRegistry
{
    /**
     * @var EntityMetadata[]
     */
    protected $loadedMetadata;

    /**
     * @var MetadataFactory
     */
    protected $factory;

    /**
     * @param MetadataFactory $factory
     */
    public function __construct(MetadataFactory $factory)
    {
        $this->factory = $factory;
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

        return $this->loadedMetadata[$className] = $this->factory->createEntityMetadata($className);
    }
}
