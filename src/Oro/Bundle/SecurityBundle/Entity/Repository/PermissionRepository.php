<?php

namespace Oro\Bundle\SecurityBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\SecurityBundle\Entity\Permission;

class PermissionRepository extends EntityRepository
{
    /**
     * @param array $ids
     * @return Permission[]
     */
    public function findByIds(array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('p');

        return $queryBuilder->where($queryBuilder->expr()->in('p.id', $ids))
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $class
     * @param array $ids
     * @return Permission[]
     */
    public function findByEntityClassAndIds($class, array $ids = null)
    {
        if (empty($class) || null !== $ids && empty($ids)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder
            ->leftJoin('p.applyToEntities', 'ae', Expr\Join::WITH, 'ae.name = :class')
            ->leftJoin('p.excludeEntities', 'ee', Expr\Join::WITH, 'ee.name = :class')
            ->groupBy('p.id')
            ->having(
                $queryBuilder->expr()->orx(
                    $queryBuilder->expr()->andx(
                        $queryBuilder->expr()->eq('p.applyToAll', 1),
                        $queryBuilder->expr()->eq($queryBuilder->expr()->count('ee'), 0)
                    ),
                    $queryBuilder->expr()->andx(
                        $queryBuilder->expr()->eq('p.applyToAll', 0),
                        $queryBuilder->expr()->gt($queryBuilder->expr()->count('ae'), 0)
                    )
                )
            )
            ->setParameter('class', $class);

        if (null !== $ids) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('p.id', $ids));
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
