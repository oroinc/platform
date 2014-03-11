<?php

namespace Oro\Bundle\ReminderBundle\Entity;

use Doctrine\Common\Collections\Collection;

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
}
