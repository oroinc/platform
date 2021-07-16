<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * Provides a set of methods to load scopes from the database.
 * @internal intended to be used by ScopeManager only
 */
class ScopeDataAccessor
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var CacheProvider */
    private $scopeCache;

    /** @var ScopeCacheKeyBuilderInterface */
    private $scopeCacheKeyBuilder;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        CacheProvider $scopeCache,
        ScopeCacheKeyBuilderInterface $scopeCacheKeyBuilder
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->scopeCache = $scopeCache;
        $this->scopeCacheKeyBuilder = $scopeCacheKeyBuilder;
    }

    public function findMostSuitableByCriteria(ScopeCriteria $criteria): ?Scope
    {
        $qb = $this->doctrineHelper->createQueryBuilder(Scope::class, 'scope');
        $criteria->applyWhereWithPriority($qb, 'scope');
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param ScopeCriteria $criteria
     *
     * @return BufferedQueryResultIteratorInterface|Scope[]
     */
    public function findByCriteria(ScopeCriteria $criteria): iterable
    {
        $qb = $this->doctrineHelper->createQueryBuilder(Scope::class, 'scope');
        $criteria->applyWhere($qb, 'scope');

        return new BufferedIdentityQueryResultIterator($qb);
    }

    public function findOneByCriteria(ScopeCriteria $criteria): ?Scope
    {
        $qb = $this->doctrineHelper->createQueryBuilder(Scope::class, 'scope');
        $criteria->applyWhere($qb, 'scope');
        $query = $qb->setMaxResults(1)->getQuery();
        $cacheKey = $this->scopeCacheKeyBuilder->getCacheKey($criteria);
        if (null !== $cacheKey) {
            $query
                ->useResultCache(true, null, $cacheKey)
                ->setResultCacheDriver($this->scopeCache);
        }

        return $query->getOneOrNullResult();
    }

    public function findIdentifierByCriteria(ScopeCriteria $criteria): ?int
    {
        $cacheKey = $this->scopeCacheKeyBuilder->getCacheKey($criteria);
        if (null === $cacheKey) {
            return $this->loadIdentifierByCriteria($criteria);
        }

        $resultKey = 'id:' . $criteria->getIdentifier();
        $data = $this->scopeCache->fetch($cacheKey);
        if (false !== $data && \array_key_exists($resultKey, $data)) {
            return $data[$resultKey];
        }

        $id = $this->loadIdentifierByCriteria($criteria);
        $data[$resultKey] = $id;
        $this->scopeCache->save($cacheKey, $data);

        return $id;
    }

    /**
     * @param ScopeCriteria $criteria
     *
     * @return int[]
     */
    public function findIdentifiersByCriteria(ScopeCriteria $criteria): array
    {
        $cacheKey = $this->scopeCacheKeyBuilder->getCacheKey($criteria);
        if (null === $cacheKey) {
            return $this->loadIdentifiersByCriteria($criteria);
        }

        $resultKey = 'ids:' . $criteria->getIdentifier();
        $data = $this->scopeCache->fetch($cacheKey);
        if (false !== $data && isset($data[$resultKey])) {
            return $data[$resultKey];
        }

        $ids = $this->loadIdentifiersByCriteria($criteria);
        $data[$resultKey] = $ids;
        $this->scopeCache->save($cacheKey, $data);

        return $ids;
    }

    /**
     * @param ScopeCriteria $criteria
     *
     * @return int[]
     */
    public function findIdentifiersByCriteriaWithPriority(ScopeCriteria $criteria): array
    {
        $cacheKey = $this->scopeCacheKeyBuilder->getCacheKey($criteria);
        if (null === $cacheKey) {
            return $this->loadIdentifiersByCriteriaWithPriority($criteria);
        }

        $resultKey = 'ids_priority:' . $criteria->getIdentifier();
        $data = $this->scopeCache->fetch($cacheKey);
        if (false !== $data && isset($data[$resultKey])) {
            return $data[$resultKey];
        }

        $ids = $this->loadIdentifiersByCriteriaWithPriority($criteria);
        $data[$resultKey] = $ids;
        $this->scopeCache->save($cacheKey, $data);

        return $ids;
    }

    private function loadIdentifierByCriteria(ScopeCriteria $criteria): ?int
    {
        $qb = $this->doctrineHelper->createQueryBuilder(Scope::class, 'scope');
        $criteria->applyWhere($qb, 'scope');
        $query = $qb->select('scope.id')->setMaxResults(1)->getQuery();

        $scopes = $query->getScalarResult();
        if (!$scopes) {
            return null;
        }

        return (int)$scopes[0]['id'];
    }

    /**
     * @param ScopeCriteria $criteria
     *
     * @return int[]
     */
    private function loadIdentifiersByCriteria(ScopeCriteria $criteria): array
    {
        $qb = $this->doctrineHelper->createQueryBuilder(Scope::class, 'scope');
        $criteria->applyWhere($qb, 'scope');

        return $this->findIdentifiers($qb);
    }

    /**
     * @param ScopeCriteria $criteria
     *
     * @return int[]
     */
    private function loadIdentifiersByCriteriaWithPriority(ScopeCriteria $criteria): array
    {
        $qb = $this->doctrineHelper->createQueryBuilder(Scope::class, 'scope');
        $criteria->applyWhereWithPriority($qb, 'scope');

        return $this->findIdentifiers($qb);
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return int[]
     */
    private function findIdentifiers(QueryBuilder $qb): array
    {
        $query = $qb->select('scope.id')->getQuery();
        $scopes = $query->getScalarResult();

        $ids = [];
        foreach ($scopes as $scope) {
            $ids[] = (int)$scope['id'];
        }

        return $ids;
    }
}
