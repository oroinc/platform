<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Represents a state of the {@see SegmentQueryConverter} that is used to optimize performance
 * by caching already built ORM queries and prevent naming conflicts in table aliases when the cache is used
 * and when the same segment is used several times by other segments.
 */
class SegmentQueryConverterState
{
    private CacheItemPoolInterface $cache;
    /** [segment id => the number of registered queries, ...] */
    private array $queries = [];
    private int $numberOfRegisteredQueries = 0;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function registerQuery(int $segmentId): void
    {
        if (isset($this->queries[$segmentId])) {
            $this->queries[$segmentId]++;
        } else {
            $this->queries[$segmentId] = 1;
        }
        $this->numberOfRegisteredQueries++;
    }

    public function unregisterQuery(int $segmentId): void
    {
        if (!$this->isQueryRegistered($segmentId)) {
            throw new \LogicException(sprintf(
                'Cannot unregister a query for the segment %d because it was not registered yet.',
                $segmentId
            ));
        }

        $this->queries[$segmentId]--;
        if (0 === $this->queries[$segmentId] && $this->isAllQueriesUnregistered()) {
            $this->queries = [];
            $this->numberOfRegisteredQueries = 0;
        }
    }

    public function isRootQuery(int $segmentId): bool
    {
        return
            1 === $this->numberOfRegisteredQueries
            && isset($this->queries[$segmentId])
            && 1 === $this->queries[$segmentId];
    }

    public function buildQueryAlias(int $segmentId, AbstractQueryDesigner $segment): string
    {
        if (!$this->isQueryRegistered($segmentId)) {
            throw new \LogicException(sprintf(
                'A query for the segment %d was not registered yet.',
                $segmentId
            ));
        }

        return UniversalCacheKeyGenerator::normalizeCacheKey(
            md5($segment->getEntity() . '::' . $segment->getDefinition())
            . '_'
            . $this->numberOfRegisteredQueries
        );
    }

    public function getQueryFromCache(int $segmentId): ?QueryBuilder
    {
        $queryBuilderItem = $this->cache->getItem($this->getQueryCacheKey($segmentId));

        return $queryBuilderItem->isHit()
            ? clone $queryBuilderItem->get()
            : null;
    }

    public function saveQueryToCache(int $segmentId, QueryBuilder $queryBuilder): void
    {
        $queryBuilderItem = $this->cache->getItem($this->getQueryCacheKey($segmentId));
        $this->cache->save($queryBuilderItem->set(clone $queryBuilder));
    }

    private function getQueryCacheKey(int $segmentId): string
    {
        return 'segment_query_' . $segmentId;
    }

    private function isQueryRegistered(int $segmentId): bool
    {
        return isset($this->queries[$segmentId]) && $this->queries[$segmentId] > 0;
    }

    private function isAllQueriesUnregistered(): bool
    {
        if ($this->queries) {
            foreach ($this->queries as $counter) {
                if ($counter > 0) {
                    return false;
                }
            }
        }

        return true;
    }
}
