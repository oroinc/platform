<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\Email;

class EmailThreadProvider
{
    /**
     * Gets threadId
     *
     * @param EntityManager $entityManager
     * @param Email $entity
     *
     * @return string
     */
    public function getEmailThreadId(EntityManager $entityManager, Email $entity)
    {
        // search among threadId
        $emailReferences = $this->getEmailReferences($entityManager, $entity);
        foreach ($emailReferences as $email) {
            /** @var Email $email */
            if ($email->getThreadId()) {
                return $email->getThreadId();
            }
        }
        // search among xThreadId
        foreach ($emailReferences as $email) {
            /** @var Email $email */
            if ($email->getXThreadId()) {
                return $email->getXThreadId();
            }
        }
        $threadId = $entity->getThreadId();
        if (!$threadId && $entity->getXThreadId()) {
            $threadId = $entity->getXThreadId();
        }
        // generate new thread if need
        if (!$threadId && count($emailReferences) > 0) {
            $threadId = $this->generateThreadId();
        }
        return $threadId;
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
        $threadId = $entity->getThreadId();
        if ($threadId) {
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
        $threadId = $entity->getThreadId();
        if ($threadId) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $entityManager->getRepository('OroEmailBundle:Email')->createQueryBuilder('e');
            $criteria = new Criteria();
            $criteria->where($criteria->expr()->eq('threadId', $threadId));
            $criteria->orderBy(['sentAt' => Criteria::DESC]);
            $queryBuilder->addCriteria($criteria);
            $result = $queryBuilder->getQuery()->getResult();
        } else {
            $result = [$entity];
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function generateThreadId()
    {
        return md5(getmypid() . '.' . time() . '.' . uniqid(mt_rand(), true));
    }
}
