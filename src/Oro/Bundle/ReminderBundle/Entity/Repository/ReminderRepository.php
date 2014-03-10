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
    public function findRemindersToSend()
    {
        return $this->createQueryBuilder('reminder')
            ->where('reminder.state != :sent_state')
            ->andWhere('reminder.startAt <= :now')
            ->andWhere('reminder.expireAt >= :now')
            ->setParameter('now', new \DateTime())
            ->setParameter('sent_state', Reminder::STATE_SENT)
            ->getQuery()
            ->execute();
    }
}
