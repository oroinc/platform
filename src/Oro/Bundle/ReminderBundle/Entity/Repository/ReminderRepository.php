<?php

namespace Oro\Bundle\ReminderBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\WebSocket\WebSocketSendProcessor;
use Oro\Bundle\UserBundle\Entity\User;

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
            ->where('reminder.state = :state')
            ->andWhere('reminder.startAt <= :now')
            ->andWhere('reminder.expireAt >= :now')
            ->setParameter('now', new \DateTime())
            ->setParameter('state', Reminder::STATE_NOT_SENT)
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

    /**
     * Find only requested reminders assigned to user
     *
     * @param User $user
     * @return Reminder[]
     */
    public function findRequestedReminders(User $user)
    {
        return $this->createQueryBuilder('reminder')
            ->where('reminder.state = :sent_state')
            ->andWhere('reminder.recipient = :userId')
            ->andWhere('reminder.method = :method')
            ->setParameter('userId', $user->getId())
            ->setParameter('method', WebSocketSendProcessor::NAME)
            ->setParameter('sent_state', Reminder::STATE_REQUESTED)
            ->getQuery()
            ->execute();
    }

    /**
     * Find reminders by reminder ids
     *
     * @param array $reminderIds
     * @return Reminder[]
     */
    public function findReminders(array $reminderIds)
    {
        $qb = $this->createQueryBuilder('reminder');

        return $qb->where($qb->expr()->in('reminder.id', $reminderIds))->getQuery()->execute();
    }

    /**
     * Returns a query builder which can be used to get reminders for the given entities of the given type
     *
     * @param string $entityClassName
     * @param int[]  $entityIds
     *
     * @return QueryBuilder
     */
    public function findRemindersByEntitiesQueryBuilder($entityClassName, array $entityIds)
    {
        return $this->createQueryBuilder('reminder')
            ->where('reminder.relatedEntityClassName = :className AND reminder.relatedEntityId IN (:ids)')
            ->setParameter('className', $entityClassName)
            ->setParameter('ids', $entityIds);
    }
}
