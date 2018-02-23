<?php

namespace Oro\Bundle\ReminderBundle\Event;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Symfony\Component\EventDispatcher\Event;

class SendReminderEmailEvent extends Event
{
    /**
     * @var Reminder
     */
    protected $reminder;

    /**
     * @param Reminder $reminder
     */
    public function __construct(Reminder $reminder)
    {
        $this->reminder = $reminder;
    }

    /**
     * @return Reminder
     */
    public function getReminder()
    {
        return $this->reminder;
    }
}
