<?php

namespace Oro\Bundle\SegmentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class SegmentRepository extends EntityRepository
{
    /**
     * @param string $entityClass
     * @return string[]
     */
    public function findByEntity($entityClass)
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.name')
            ->where('s.entity = :entity')
            ->setParameter('entity', $entityClass);

        $result = $qb->getQuery()->getArrayResult();

        return array_column($result, 'name');
    }
}
