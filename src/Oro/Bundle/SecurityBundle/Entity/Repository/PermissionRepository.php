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
        if (empty($class)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder
            ->leftJoin('p.applyToEntities', 'ae', Expr\Join::WITH, 'ae.name = :class')
            ->leftJoin('p.excludeEntities', 'ee', Expr\Join::WITH, 'ee.name = :class')
            ->where(
                $queryBuilder->expr()->andx(
                    $queryBuilder->expr()->orx(
                        $queryBuilder->expr()->eq('p.applyToAll', '1'),
                        $queryBuilder->expr()->isNotNull('ae.id')
                    ),
                    $queryBuilder->expr()->isNull('ee.name')
                )
            )
            ->setParameter('class', $class);

        if (null !== $ids) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('p.id', $ids));
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
