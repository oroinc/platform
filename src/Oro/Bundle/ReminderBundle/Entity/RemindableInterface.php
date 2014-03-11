<?php

namespace Oro\Bundle\ReminderBundle\Entity;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ReminderBundle\Model\ReminderDataInterface;

interface RemindableInterface
{
    /**
     * @return Collection
     */
    public function getReminders();

    /**
     * @param Collection $reminders
     */
    public function setReminders(Collection $reminders);

    /**
     * @return ReminderDataInterface
     */
    public function getReminderData();
}
