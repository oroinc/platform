<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\ReminderBundle\Entity\Reminder;

/**
 * Responsible for sending reminder.
 */
interface ReminderSenderInterface
{
    /**
     * Push reminder for sending
     *
     * @param Reminder $reminder
     */
    public function push(Reminder $reminder);

    /**
     * Send all reminders
     */
    public function send();
}
