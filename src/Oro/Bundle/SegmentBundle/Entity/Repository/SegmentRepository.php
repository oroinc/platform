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
            ->select('s.id, s.name')
            ->where('s.entity = :entity')
            ->setParameter('entity', $entityClass);

        $segments = $qb->getQuery()->getArrayResult();

        $result = [];
        foreach ($segments as $segment) {
            $result[$segment['name']] = $segment['id'];
        }

        return $result;
    }
}
