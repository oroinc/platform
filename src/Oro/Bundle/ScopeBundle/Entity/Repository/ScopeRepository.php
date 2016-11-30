<?php

namespace Oro\Bundle\ScopeBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class ScopeRepository extends EntityRepository
{
    /**
     * @param ScopeCriteria $criteria
     * @return BufferedQueryResultIterator|Scope[]
     */
    public function findByCriteria(ScopeCriteria $criteria)
    {
        $qb = $this->createQueryBuilder('scope');
        $criteria->applyWhere($qb, 'scope');

        return new BufferedQueryResultIterator($qb);
    }

    /**
     * @param ScopeCriteria $criteria
     * @return BufferedQueryResultIterator|Scope[]
     */
    public function findIdentifiersByCriteria(ScopeCriteria $criteria)
    {
        $qb = $this->createQueryBuilder('scope');
        $criteria->applyWhere($qb, 'scope');

        return $this->findIdentifiers($qb);
    }

    /**
     * @param ScopeCriteria $criteria
     *
     * @return array
     */
    public function findIdentifiersByCriteriaWithPriority(ScopeCriteria $criteria)
    {
        $qb = $this->createQueryBuilder('scope');
        $criteria->applyWhereWithPriority($qb, 'scope');

        return $this->findIdentifiers($qb);
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return array
     */
    private function findIdentifiers(QueryBuilder $qb)
    {
        $scopes = $qb->select('scope.id')
            ->getQuery()
            ->getScalarResult();

        $ids = [];
        foreach ($scopes as $scope) {
            $ids[] = (int)$scope['id'];
        }

        return $ids;
    }

    /**
     * @param ScopeCriteria $criteria
     * @return Scope
     */
    public function findOneByCriteria(ScopeCriteria $criteria)
    {
        $qb = $this->createQueryBuilder('scope');
        $criteria->applyWhere($qb, 'scope');
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
