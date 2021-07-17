<?php

namespace Oro\Component\MessageQueue\Event;

use Oro\Component\MessageQueue\Job\Job;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is dispatched each time after job is saved.
 */
class AfterSaveJobEvent extends Event
{
    public const EVENT_ALIAS = 'oro_message_queue.after_save_job';

    /** @var Job */
    private $job;

    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    public function getJob(): Job
    {
        return $this->job;
    }
}
