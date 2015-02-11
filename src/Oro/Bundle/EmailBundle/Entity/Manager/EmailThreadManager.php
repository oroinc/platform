<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\Email;

class EmailThreadManager
{
    /**
     * Handle onFlush event
     *
     * @param OnFlushEventArgs $event
     */
    public function handleOnFlush(OnFlushEventArgs $event)
    {
        $entityManager = $event->getEntityManager();
        $uow = $entityManager->getUnitOfWork();

        $newEntities = $uow->getScheduledEntityInsertions();
        foreach ($newEntities as $entity) {
            if ($entity instanceof Email) {
                if ($entity->getThreadId()) {
                    continue;
                }
                if ($entity->getXThreadId()) {
                    $entity->setThreadId($entity->getXThreadId());
                } elseif ($entity->getRefs()) {
                    /** @var QueryBuilder $queryBuilder */
                    $queryBuilder = $entityManager->getRepository('OroEmailBundle:Email')->createQueryBuilder('e');
                    $criteria = new Criteria();
                    $criteria->where($criteria->expr()->neq('threadId', null));
                    $criteria->andWhere($criteria->expr()->in('messageId', explode(' ', $entity->getRefs())));
                    $queryBuilder->addCriteria($criteria);
                    /** @var Email $email */
                    $email = $queryBuilder->getQuery()->getSingleResult();
                    if ($email) {
                        $entity->setThreadId($email->getThreadId());
                    }
                } else {
                    $entity->setThreadId(uniqid());
                }
            }
        }
    }
}
