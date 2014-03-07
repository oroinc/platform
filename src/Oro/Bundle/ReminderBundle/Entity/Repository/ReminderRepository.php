<?php

namespace Oro\Bundle\ReminderBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ReminderBundle\Entity\Reminder;

class ReminderRepository extends EntityRepository
{
    /**
     * @return Reminder[]
     */
    public function getRemindersToProcess()
    {
        return $this->createQueryBuilder('r')
            ->where('r.isSent = false')
            ->getQuery()
            ->execute();
    }
}
