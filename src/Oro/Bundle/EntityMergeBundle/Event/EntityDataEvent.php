<?php

namespace Oro\Bundle\EntityMergeBundle\Event;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Symfony\Contracts\EventDispatcher\Event;

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
