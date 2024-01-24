<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Provides data related to email threads.
 */
class EmailThreadProvider
{
    /**
     * Gets threadId
     *
     * @param EntityManager $entityManager
     * @param Email $entity
     *
     * @return EmailThread
     */
    public function getEmailThread(EntityManager $entityManager, Email $entity)
    {
        $thread = null;

        // search among threadId
        $emailReferences = $this->getEmailReferences($entityManager, $entity);
        foreach ($emailReferences as $email) {
            /** @var Email $email */
            if ($email->getThread()) {
                return $email->getThread();
            }
        }

        // generate new thread if need
        if (count($emailReferences) > 0) {
            $thread = new EmailThread();
        }

        return $thread;
    }

    /**
     * Gets email references of current one
     *
     * @param EntityManager $entityManager
     * @param Email $entity
     *
     * @return array
     */
    public function getEmailReferences(EntityManager $entityManager, Email $entity)
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
     * @param EntityManager $entityManager
     * @param Email $entity
     *
     * @return Email[]
     */
    public function getReferredEmails(EntityManager $entityManager, Email $entity): array
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
     * Get head email in thread
     *
     * @param EntityManager $entityManager
     * @param Email         $entity
     *
     * @return Email
     */
    public function getHeadEmail(EntityManager $entityManager, Email $entity)
    {
        $headEmail = $entity;
        $thread = $entity->getThread();
        if ($thread) {
            $emails = new ArrayCollection($this->getThreadEmails($entityManager, $entity));
            $criteria = new Criteria();
            $criteria->orderBy(['sentAt' => Criteria::DESC]);
            $criteria->setMaxResults(1);
            $unseenEmails = $emails->matching($criteria);
            if (count($unseenEmails)) {
                $headEmail = $unseenEmails[0];
            } elseif (count($emails)) {
                $headEmail = $emails[0];
            }
        }

        return $headEmail;
    }

    /**
     * Get emails in thread of current one
     *
     * @param EntityManager $entityManager
     * @param Email $entity
     *
     * @return Email[]
     */
    public function getThreadEmails(EntityManager $entityManager, Email $entity)
    {
        $thread = $entity->getThread();
        if ($thread) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $entityManager->getRepository('OroEmailBundle:Email')->createQueryBuilder('e');
            $criteria = new Criteria();
            $criteria->where($criteria->expr()->eq('thread', $thread));
            $criteria->orderBy(['sentAt' => Criteria::DESC]);
            $queryBuilder->addCriteria($criteria);
            $result = $queryBuilder->getQuery()->getResult();
        } else {
            $result = [$entity];
        }

        return $result;
    }

    /**
     * Get emails in thread by given email.
     * Used on `My Emails` page to show emails thread with only emails being related to currently logged user.
     *
     * @param EntityManager $entityManager
     * @param Email         $entity
     * @param User          $user
     * @param array         $mailboxes
     * @return array
     */
    public function getUserThreadEmails(EntityManager $entityManager, Email $entity, User $user, $mailboxes = [])
    {
        $thread = $entity->getThread();
        if ($thread) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $entityManager->getRepository('OroEmailBundle:Email')->createQueryBuilder('e');
            $queryBuilder->join('e.emailUsers', 'eu')
                ->where($queryBuilder->expr()->eq('e.thread', ':thread'))
                ->setParameter('thread', $thread)
                ->orderBy('e.sentAt', Criteria::DESC);

            if ($mailboxes) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->in('eu.mailboxOwner', ':mailboxes'),
                        $queryBuilder->expr()->eq('eu.owner', ':user')
                    )
                )->setParameter('mailboxes', $mailboxes);
            } else {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq('eu.owner', ':user')
                );
            }
            $queryBuilder->setParameter('user', $user);

            $result = $queryBuilder
                ->getQuery()
                ->getResult();
        } else {
            $result = [$entity];
        }

        return $result;
    }
}
