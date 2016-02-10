<?php

namespace Oro\Bundle\SecurityBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
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

        return $this->addFindByIdsCriteria($this->createQueryBuilder('p'), $ids)
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

        $this->addFindByEntityClassCriteria($queryBuilder, $class);

        if ($ids) {
            $this->addFindByIdsCriteria($queryBuilder, $class);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $ids
     * @return QueryBuilder
     */
    public function addFindByIdsCriteria(QueryBuilder $queryBuilder, array $ids)
    {
        $alias = $queryBuilder->getRootAlias();

        return $queryBuilder->where($queryBuilder->expr()->in($alias . '.id', $ids));
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $class
     * @return QueryBuilder
     */
    public function addFindByEntityClassCriteria(QueryBuilder $queryBuilder, $class)
    {
        $alias = $queryBuilder->getRootAlias();

        $queryBuilder
            ->leftJoin($alias . '.applyToEntities', 'ae', Expr\Join::WITH, 'ae.name = :class')
            ->leftJoin($alias . '.excludeEntities', 'ee', Expr\Join::WITH, 'ee.name = :class')
            ->groupBy($alias . '.id')
            ->having(
                $queryBuilder->expr()->orx(
                    $queryBuilder->expr()->andx(
                        $queryBuilder->expr()->eq($alias . '.applyToAll', 1),
                        $queryBuilder->expr()->eq($queryBuilder->expr()->count('ee'), 0)
                    ),
                    $queryBuilder->expr()->andx(
                        $queryBuilder->expr()->eq($alias . '.applyToAll', 0),
                        $queryBuilder->expr()->gt($queryBuilder->expr()->count('ae'), 0)
                    )
                )
            )
            ->setParameter('class', $class);

        return $queryBuilder;
    }
}
