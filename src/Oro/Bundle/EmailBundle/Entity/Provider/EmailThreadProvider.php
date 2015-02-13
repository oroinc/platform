<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

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
        foreach ($this->getEmailReferences($entityManager, $entity) as $email) {
            /** @var Email $email */
            if ($email->getThreadId()) {
                return $email->getThreadId();
            }
        }
        // search among xThreadId
        foreach ($this->getEmailReferences($entityManager, $entity) as $email) {
            /** @var Email $email */
            if ($email->getXThreadId()) {
                return $email->getXThreadId();
            }
        }
        $threadId = $entity->getThreadId();
        if (!$threadId && $entity->getXThreadId()) {
            $threadId = $entity->getXThreadId();
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
     * Get emails in thread of current one
     *
     * @param EntityManager $entityManager
     * @param Email $entity
     *
     * @return Email[]
     */
    public function getThreadEmails(EntityManager $entityManager, Email $entity)
    {
        $result = [];
        $threadId = $entity->getThreadId();
        if ($threadId) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $entityManager->getRepository('OroEmailBundle:Email')->createQueryBuilder('e');
            $criteria = new Criteria();
            $criteria->where($criteria->expr()->eq('threadId', $threadId));
            $criteria->orderBy(['sentAt'=>Criteria::DESC]);
            $queryBuilder->addCriteria($criteria);
            $result = $queryBuilder->getQuery()->getResult();
        }

        return $result;
    }
}
