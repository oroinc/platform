<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Provides data related to email threads.
 */
class EmailThreadProvider
{
    /**
     * Gets all emails referenced by the given email.
     *
     * @return Email[]
     */
    public function getEmailReferences(EntityManagerInterface $entityManager, Email $entity): array
    {
        $refs = $entity->getRefs();
        if (!$refs) {
            return [];
        }

        return $entityManager->createQueryBuilder()
            ->select('e')
            ->from(Email::class, 'e')
            ->where('e.messageId IN (:messagesIds)')
            ->setParameter('messagesIds', $refs)
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets all emails that are referred to the given email.
     *
     * @return Email[]
     */
    public function getReferredEmails(EntityManagerInterface $entityManager, Email $entity): array
    {
        if ($entity->getRefs()) {
            return [];
        }

        $messageId = $entity->getMessageId();
        if (!$messageId) {
            return [];
        }

        return $entityManager->createQueryBuilder()
            ->select('e')
            ->from(Email::class, 'e')
            ->where('e.refs LIKE :messagesId')
            ->setParameter('messagesId', '%' . $messageId . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets the head email in a thread.
     */
    public function getHeadEmail(EntityManagerInterface $entityManager, Email $entity): Email
    {
        $thread = $entity->getThread();
        if (!$thread) {
            return $entity;
        }

        $threadEmail = $entityManager->createQueryBuilder()
            ->select('e')
            ->from(Email::class, 'e')
            ->where('e.thread = :thread')
            ->setParameter('thread', $thread)
            ->orderBy('e.sentAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $threadEmail ?? $entity;
    }

    /**
     * Gets all emails in a thread.
     *
     * @return Email[]
     */
    public function getThreadEmails(EntityManagerInterface $entityManager, Email $entity): array
    {
        $thread = $entity->getThread();
        if (!$thread) {
            return [$entity];
        }

        return $entityManager->createQueryBuilder()
            ->select('e')
            ->from(Email::class, 'e')
            ->where('e.thread = :thread')
            ->setParameter('thread', $thread)
            ->orderBy('e.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets all emails in a thread filtered by the current logged in user.
     * Used on "My Emails" page to show emails thread with only emails being related to currently logged user.
     *
     * @return Email[]
     */
    public function getUserThreadEmails(
        EntityManagerInterface $entityManager,
        Email $entity,
        User $user,
        array $mailboxes = []
    ): array {
        $thread = $entity->getThread();
        if (!$thread) {
            return [$entity];
        }

        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('e')
            ->from(Email::class, 'e')
            ->innerJoin('e.emailUsers', 'eu')
            ->where('e.thread = :thread')
            ->setParameter('thread', $thread)
            ->setParameter('user', $user)
            ->orderBy('e.sentAt', 'DESC');

        if ($mailboxes) {
            $queryBuilder
                ->andWhere('eu.mailboxOwner IN (:mailboxes) OR eu.owner = :user')
                ->setParameter('mailboxes', $mailboxes);
        } else {
            $queryBuilder->andWhere('eu.owner = :user');
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
