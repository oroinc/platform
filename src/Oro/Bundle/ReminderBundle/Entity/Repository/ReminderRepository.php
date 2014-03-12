<?php

namespace Oro\Bundle\ReminderBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\WebSocket\WebSocketSendProcessor;

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
     * @param $userId
     * @return Reminder[]
     */
    public function findRequestedReminders($userId)
    {
        return $this->createQueryBuilder('reminder')
            ->where('reminder.state = :sent_state')
            ->andWhere('reminder.recipient = :userId')
            ->andWhere('reminder.method = :method')
            ->setParameter('userId', $userId)
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
     * @param array $entityIds
     * @param string $entityClassName
     * @return Reminder[]
     */
    public function findRemindersByEntities(array $entityIds, $entityClassName)
    {
        $qb = $this->createQueryBuilder('reminder')
            ->where('reminder.relatedEntityClassName = :entityClassName')
            ->andWhere('reminder.relatedEntityId IN (:entityIds)')
            ->setParameter('entityClassName', $entityClassName)
            ->setParameter('entityIds', $entityIds);

        return $qb->getQuery()->execute();
    }
}
