<?php

namespace Oro\Bundle\ReportBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ReportRepository extends EntityRepository
{
    /**
     * @param array $excludedEntitiesClasses
     * @return QueryBuilder
     */
    public function getAllReportsBasicInfoQb(array $excludedEntitiesClasses = [])
    {
        $qb = $this->createQueryBuilder('report')
            ->select('report.id, report.entity, report.name')
            ->orderBy('report.name', 'ASC');

        if ($excludedEntitiesClasses) {
            $qb
                ->andWhere($qb->expr()->notIn('report.entity', ':excluded_entities'))
                ->setParameter('excluded_entities', $excludedEntitiesClasses);
        }
        return $qb;
    }
}
