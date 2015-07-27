<?php

namespace Oro\Bundle\SecurityBundle\Entity\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;

class AclEntryRepository extends EntityRepository
{
    /**
     * Check if entity record was shared
     *
     * @param object $entity
     * @return bool
     */
    public function isEntityShared($entity)
    {
        if (null === $entity || !is_object($entity)) {
            return false;
        }

        $queryBuilder = $this->createQueryBuilder('e')
            ->leftJoin('e.class', 'c')
            ->where('c.classType = :cName AND e.recordId = :entityId')
            ->setParameter('cName', ClassUtils::getClass($entity))
            ->setParameter('entityId', $entity->getId())
            ->setMaxResults(1);

        $item = $queryBuilder->getQuery()->getOneOrNullResult();

        return $item ? true : false;
    }
}
