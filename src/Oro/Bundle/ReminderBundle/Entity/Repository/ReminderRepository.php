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

    /**
     * Find reminders by entity class and entity id
     *
     * @param string $entityClassName
     * @param integer $entityId
     * @return Reminder[]
     */
    public function findRemindersByEntity($entityClassName, $entityId)
    {
        return $this->createQueryBuilder('reminder')
            ->where('reminder.relatedEntityClassName = :entityClassName')
            ->andWhere('reminder.relatedEntityId = :entityId')
            ->setParameter('entityClassName', $entityClassName)
            ->setParameter('entityId', $entityId)
            ->getQuery()
            ->execute();
    }
}
