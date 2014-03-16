<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\ReminderBundle\Entity\Reminder;

/**
 * Responsible for sending reminder.
 */
interface ReminderSenderInterface
{
    public function send(Reminder $reminder);
}
