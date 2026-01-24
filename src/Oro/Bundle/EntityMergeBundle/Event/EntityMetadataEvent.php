<?php

namespace Oro\Bundle\EntityMergeBundle\Event;

use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when entity merge metadata is built.
 *
 * This event is triggered after the merge metadata for an entity class is constructed,
 * allowing listeners to inspect or modify the metadata before it is cached and used
 * in merge operations. Listeners can use this event to add custom fields, adjust merge
 * modes, or apply other metadata customizations.
 */
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
