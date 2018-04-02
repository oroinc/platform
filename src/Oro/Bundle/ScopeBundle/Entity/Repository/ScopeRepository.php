<?php

namespace Oro\Bundle\ScopeBundle\Entity\Repository;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class ScopeRepository extends EntityRepository
{
    const SCOPE_RESULT_CACHE_KEY_ID = 'oro_scope_result_cache_key_id';

    /**
     * @var CacheProvider
     */
    private $scopeRepositoryCache;

    /**
     * @param CacheProvider $scopeRepositoryCache
     */
    public function setScopeRepositoryCache(CacheProvider $scopeRepositoryCache)
    {
        $this->scopeRepositoryCache = $scopeRepositoryCache;
    }

    /**
     * @param ScopeCriteria $criteria
     * @return BufferedQueryResultIteratorInterface|Scope[]
     */
    public function findByCriteria(ScopeCriteria $criteria)
    {
        $qb = $this->createQueryBuilder('scope');
        $criteria->applyWhere($qb, 'scope');

        return new BufferedIdentityQueryResultIterator($qb);
    }

    /**
     * @param ScopeCriteria $criteria
     * @return BufferedQueryResultIteratorInterface|Scope[]
     */
    public function findIdentifiersByCriteria(ScopeCriteria $criteria)
    {
        $qb = $this->createQueryBuilder('scope');
        $criteria->applyWhere($qb, 'scope');

        return $this->findIdentifiers($qb);
    }

    /**
     * Find most suitable scope from already existing scopes
     *
     * @param ScopeCriteria $criteria
     * @return Scope|null
     */
    public function findMostSuitable(ScopeCriteria $criteria)
    {
        $qb = $this->createQueryBuilder('scope');
        $criteria->applyWhereWithPriority($qb, 'scope');
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
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
     * @param ScopeCriteria $criteria
     * @return Scope
     */
    public function findOneByCriteria(ScopeCriteria $criteria)
    {
        $qb = $this->createQueryBuilder('scope');
        $criteria->applyWhere($qb, 'scope');
        $query = $qb->setMaxResults(1)->getQuery();
        if ($this->scopeRepositoryCache) {
            $query
                ->useResultCache(true)
                ->setResultCacheId(self::SCOPE_RESULT_CACHE_KEY_ID)
                ->setResultCacheDriver($this->scopeRepositoryCache);
        }

        return $query->getOneOrNullResult();
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return array
     */
    private function findIdentifiers(QueryBuilder $qb)
    {
        $query = $qb->select('scope.id')->getQuery();
        if ($this->scopeRepositoryCache) {
            $query
                ->useResultCache(true)
                ->setResultCacheId(self::SCOPE_RESULT_CACHE_KEY_ID)
                ->setResultCacheDriver($this->scopeRepositoryCache);
        }
        $scopes = $query->getScalarResult();

        $ids = [];
        foreach ($scopes as $scope) {
            $ids[] = (int)$scope['id'];
        }

        return $ids;
    }
}
