<?php

namespace Oro\Bundle\ActivityBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents an event that occurs when an activity is associated with a target entity.
 *
 * This event is dispatched when an activity is linked to or modified in relation to a target entity.
 * It carries both the activity object and the target entity object, allowing event listeners to
 * react to activity associations and perform custom logic such as logging, notifications, or
 * cascading updates to related entities.
 */
class ActivityEvent extends Event
{
    /** @var object */
    protected $activity;

    /** @var object */
    protected $target;

    /**
     * @param object $activity
     * @param object $target
     */
    public function __construct($activity, $target)
    {
        $this->activity = $activity;
        $this->target   = $target;
    }

    /**
     * @return object
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @return object
     */
    public function getTarget()
    {
        return $this->target;
    }
}
