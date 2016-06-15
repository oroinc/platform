<?php

namespace Oro\Bundle\DataAuditBundle\Entity\Repository;

use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;

class AuditRepository extends LogEntryRepository
{
    public function getLogEntriesQueryBuilder($entity)
    {
        $wrapped     = new EntityWrapper($entity, $this->_em);
        $objectClass = $wrapped->getMetadata()->name;
        $objectId    = $wrapped->getIdentifier();

        $qb = $this->createQueryBuilder('a')
            ->where('a.objectId = :objectId AND a.objectClass = :objectClass')
            ->orderBy('a.loggedAt', 'DESC')
            ->setParameters(compact('objectId', 'objectClass'));

        return $qb;
    }

    /**
     * @param string $entityClass
     * @param int $entityId
     *
     * @return AbstractAudit|null
     */
    public function findLastAuditForEntity($entityClass, $entityId)
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.objectId = :objectId')
            ->andWhere('a.objectClass = :objectClass')
            ->orderBy('a.version', 'DESC')
            ->setMaxResults(1)

            ->setParameter('objectId', $entityId)
            ->setParameter('objectClass', $entityClass)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }
}
