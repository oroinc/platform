<?php

namespace Oro\Bundle\EntityMergeBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;

abstract class AbstractMergeEvent extends Event
{
    /**
     * @var EntityData
     */
    protected $entityData;

    /**
     * @param EntityData $entityData
     */
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
