<?php

namespace Oro\Bundle\SegmentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Doctrine repository for Segment entity
 */
class SegmentRepository extends EntityRepository
{
    /**
     * @param AclHelper $aclHelper
     * @param string $entityClass
     * @return string[]
     */
    public function findByEntity(AclHelper $aclHelper, string $entityClass): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.id, s.name')
            ->where('s.entity = :entity')
            ->setParameter('entity', $entityClass);

        $query = $aclHelper->apply($qb);

        $result = [];
        foreach ($query->getArrayResult() as $segment) {
            $result[$segment['name']] = $segment['id'];
        }

        return $result;
    }

    /**
     * @return Segment[]
     */
    public function findByNameStartsWith(string $nameStartsWith, string $entityClass = null): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->andWhere($qb->expr()->like('s.name', ':name'))
            ->setParameter('name', $nameStartsWith . '%');

        if (null !== $entityClass) {
            $qb->andWhere('s.entity = :entity')
                ->setParameter('entity', $entityClass);
        }

        return $qb->getQuery()->getResult();
    }
}
