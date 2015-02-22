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

        // search among xThreadId
//        foreach ($emailReferences as $email) {
//            /** @var Email $email */
//            if ($email->getXThreadId()) {
//                return $email->getThread();
//            }
//        }
//        $threadId = $entity->getThreadId();
//        if (!$threadId && $entity->getXThreadId()) {
//            $threadId = $thread->getId();
////            $threadId = $entity->getXThreadId();
//        }

        // generate new thread if need
        if (count($emailReferences) > 0) {
            $thread = new EmailThread();
//            $thread->setSubject($entity->getSubject());
//            $thread->setSentAt($entity->getSentAt());
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
            $criteria->where($criteria->expr()->in('messageId', explode(' ', $refs)));
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
            $criteria->andWhere($criteria->expr()->eq('seen', false));
            $criteria->orderBy(['sentAt' => Criteria::DESC]);
            $criteria->setMaxResults(1);
            $unseenEmails = $emails->matching($criteria);
            if (count($unseenEmails)) {
                $headEmail = $unseenEmails[0];
            } else if (count($emails)) {
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
//        $threadId = $entity->getThreadId();
//        if ($threadId) {
        $result = [];
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

//    /**
//     * @return string
//     */
//    protected function generateThreadId()
//    {
//        return md5(getmypid() . '.' . time() . '.' . uniqid(mt_rand(), true));
//    }
}
