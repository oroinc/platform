<?php

namespace Oro\Bundle\ReminderBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ReminderBundle\Entity\Reminder;

class ReminderRepository extends EntityRepository
{
    /**
     * Find reminders that are not sent yet
     *
     * @return Reminder[]
     */
    public function findNotSentReminders()
    {
        return $this->createQueryBuilder('reminder')
            ->where('reminder.isSent = false')
            ->getQuery()
            ->execute();
    }
}
