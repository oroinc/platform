<?php

namespace Oro\Bundle\ReminderBundle\Event;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a reminder email is about to be sent.
 *
 * This event allows listeners to inspect or modify reminder email sending behavior.
 * It carries the reminder entity that triggered the email notification, enabling
 * event subscribers to access reminder details and perform custom actions before,
 * during, or after the email is sent.
 */
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
