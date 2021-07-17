<?php

namespace Oro\Bundle\ReminderBundle\Event;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Symfony\Contracts\EventDispatcher\Event;

class SendReminderEmailEvent extends Event
{
    /**
     * @var Reminder
     */
    protected $reminder;

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
