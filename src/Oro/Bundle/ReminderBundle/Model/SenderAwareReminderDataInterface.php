<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * Extends reminder data with sender information.
 *
 * This interface defines the contract for reminder data that includes information
 * about the user who sent or initiated the reminder. It extends the base
 * ReminderDataInterface to provide access to the sender user entity, enabling
 * reminders to track and display who created or triggered the notification.
 */
interface SenderAwareReminderDataInterface extends ReminderDataInterface
{
    /**
     * @return User
     */
    public function getSender();
}
