<?php

namespace Oro\Bundle\ActivityListBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * This event allow to change activity target ids
 */
class ActivityListPreQueryBuildEvent extends Event
{
    const EVENT_NAME = 'oro_activity_list.activity_list_pre_query_build';

    /** @var string */
    protected $targetClass;

    /** @var integer */
    protected $targetId;

    /** @var integer[] */
    protected $targetIds;

    /**
     * @param string $targetClass
     * @param integer $targetId
     */
    public function __construct($targetClass, $targetId)
    {
        $this->targetClass = $targetClass;
        $this->targetId = $targetId;
    }

    /**
     * @return string
     */
    public function getTargetClass()
    {
        return $this->targetClass;
    }

    /**
     * @return integer[]
     */
    public function getTargetIds()
    {
        if (!$this->targetIds) {
            $this->targetIds = [$this->targetId];
        }

        return $this->targetIds;
    }

    /**
     * @return integer
     */
    public function getTargetId()
    {
        return $this->targetId;
    }

    /**
     * @param integer[] $targetIds
     */
    public function setTargetIds(array $targetIds)
    {
        $this->targetIds = $targetIds;
    }
}
