<?php

namespace Oro\Bundle\SecurityBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Util\ClassUtils;

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

        $item = $queryBuilder->getQuery()->getResult();

        return !empty($item)?:false;
    }
}
