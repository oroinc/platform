<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;

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
        $result = [];
        $refs = $entity->getRefs();
        if ($refs) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $entityManager->getRepository('OroEmailBundle:Email')->createQueryBuilder('e');
            $criteria = new Criteria();
            $criteria->where($criteria->expr()->in('messageId', $refs));
            $queryBuilder->addCriteria($criteria);
            $result = $queryBuilder->getQuery()->getResult();
        }

        return $result;
    }

    /**
     * Get head email in thread
     *
     * @param EntityManager $entityManager
     * @param Email $entity
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
}
