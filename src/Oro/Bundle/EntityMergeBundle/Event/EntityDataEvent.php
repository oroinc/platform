<?php

namespace Oro\Bundle\EntityMergeBundle\Event;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when entity data is created during the merge process.
 *
 * This event is triggered after an {@see EntityData} object is instantiated, allowing listeners
 * to inspect or modify the entity data before the merge operation proceeds. Listeners can
 * use this event to customize merge behavior based on the entities being merged.
 */
class EntityDataEvent extends Event
{
    /**
     * @var EntityData
     */
    protected $entityData;

    public function __construct(EntityData $entityData)
    {
        $this->entityData = $entityData;
    }

    /**
     * Get merge entity data
     *
     * @return EntityData
     */
    public function getEntityData()
    {
        return $this->entityData;
    }
}
