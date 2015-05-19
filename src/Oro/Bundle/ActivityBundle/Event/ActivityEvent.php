<?php

namespace Oro\Bundle\ActivityBundle\Event;

use Symfony\Component\EventDispatcher\Event;

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
