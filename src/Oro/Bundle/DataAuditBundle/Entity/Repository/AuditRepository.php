<?php

namespace Oro\Bundle\DataAuditBundle\Entity\Repository;

use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use Gedmo\Tool\Wrapper\EntityWrapper;

/**
 * Audit repository
 */
class AuditRepository extends LogEntryRepository
{
    /**
     * @param object $entity
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getLogEntriesQueryBuilder($entity)
    {
        $wrapped     = new EntityWrapper($entity, $this->_em);
        $objectClass = $wrapped->getMetadata()->name;
        $objectId    = $wrapped->getIdentifier();

        $qb = $this->createQueryBuilder('a')
            ->where('(a.objectId = :objectId OR a.entityId = :entityId) AND a.objectClass = :objectClass')
            ->orderBy('a.loggedAt', 'DESC')
            ->setParameter('objectId', (int) $objectId)
            ->setParameter('entityId', (string) $objectId)
            ->setParameter('objectClass', $objectClass);

        return $qb;
    }
}
