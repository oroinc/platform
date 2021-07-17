<?php

namespace Oro\Bundle\EntityMergeBundle\Event;

use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Symfony\Contracts\EventDispatcher\Event;

class EntityMetadataEvent extends Event
{
    /**
     * @var EntityMetadata
     */
    protected $entityMetadata;

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
