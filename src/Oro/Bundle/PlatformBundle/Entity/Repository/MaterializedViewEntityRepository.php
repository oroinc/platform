<?php

namespace Oro\Bundle\PlatformBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;

/**
 * Entity repository for {@see MaterializedView}.
 */
class MaterializedViewEntityRepository extends ServiceEntityRepository
{
    /**
     * @param \DateTimeInterface $dateTime
     *
     * @return string[] Array of names of the MaterializedView entities updated at older than $dateTime.
     */
    public function findOlderThan(\DateTimeInterface $dateTime): array
    {
        $queryBuilder = $this->createQueryBuilder('mv');
        $query = $queryBuilder
            ->select('mv.name')
            ->where($queryBuilder->expr()->lte('mv.updatedAt', ':dateTime'))
            ->setParameter(':dateTime', $dateTime, Types::DATETIME_MUTABLE)
            ->getQuery();

        return array_column($query->getScalarResult(), 'name');
    }
}
