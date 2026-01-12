<?php

namespace Oro\Bundle\ReminderBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ReminderBundle\Model\ReminderDataInterface;

/**
 * Defines the contract for entities that support reminders.
 *
 * Entities implementing this interface can have reminders associated with them,
 * allowing users to be notified about important events or deadlines related to
 * the entity. The interface provides methods to manage the collection of reminders
 * and to retrieve reminder-specific data for the entity.
 */
interface RemindableInterface
{
    /**
     * @return Collection
     */
    public function getReminders();

    public function setReminders(Collection $reminders);

    /**
     * @return ReminderDataInterface
     */
    public function getReminderData();
}
