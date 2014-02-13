<?php

namespace Oro\Bundle\EntityMergeBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;

class EntityMetadataEvent extends Event
{
    /**
     * @var EntityMetadata
     */
    protected $entityMetadata;

    /**
     * @param EntityMetadata $entityMetadata
     */
    public function __construct(EntityMetadata $entityMetadata)
    {
        $this->entityMetadata = $entityMetadata;
    }

    /**
     * @return EntityMetadata
     */
    public function getEntityMetadata()
    {
        return $this->entityMetadata;
    }
}
