<?php

namespace Oro\Bundle\EntityMergeBundle\Event;

use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Symfony\Component\EventDispatcher\Event;

class AbstractMetadataEvent extends Event
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
