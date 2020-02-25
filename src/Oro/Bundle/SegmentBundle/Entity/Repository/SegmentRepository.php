<?php

namespace Oro\Bundle\SegmentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

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
}
