<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

/**
 * Represents a state of the {@see SegmentQueryConverter} that is used to optimize performance
 * by caching already built ORM queries and prevent naming conflicts in table aliases when the cache is used
 * and when the same segment is used several times by other segments.
 */
class SegmentQueryConverterState
{
    /** @var Cache */
    private $cache;

    /** @var int[] [segment id => the number of registered queries, ...] */
    private $queries = [];

    /** @var int */
    private $numberOfRegisteredQueries = 0;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param int $segmentId
     */
    public function registerQuery(int $segmentId): void
    {
        if (isset($this->queries[$segmentId])) {
            $this->queries[$segmentId]++;
        } else {
            $this->queries[$segmentId] = 1;
        }
        $this->numberOfRegisteredQueries++;
    }

    /**
     * @param int $segmentId
     */
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

    /**
     * @param int $segmentId
     *
     * @return bool
     */
    public function isRootQuery(int $segmentId): bool
    {
        return
            1 === $this->numberOfRegisteredQueries
            && isset($this->queries[$segmentId])
            && 1 === $this->queries[$segmentId];
    }

    /**
     * @param int                   $segmentId
     * @param AbstractQueryDesigner $segment
     *
     * @return string
     */
    public function buildQueryAlias(int $segmentId, AbstractQueryDesigner $segment): string
    {
        if (!$this->isQueryRegistered($segmentId)) {
            throw new \LogicException(sprintf(
                'A query for the segment %d was not registered yet.',
                $segmentId
            ));
        }

        return
            md5($segment->getEntity() . '::' . $segment->getDefinition())
            . '_'
            . $this->numberOfRegisteredQueries;
    }

    /**
     * @param int $segmentId
     *
     * @return QueryBuilder|null
     */
    public function getQueryFromCache(int $segmentId): ?QueryBuilder
    {
        $queryBuilder = $this->cache->fetch($this->getQueryCacheKey($segmentId));

        return false !== $queryBuilder
            ? clone $queryBuilder
            : null;
    }

    /**
     * @param int          $segmentId
     * @param QueryBuilder $queryBuilder
     */
    public function saveQueryToCache(int $segmentId, QueryBuilder $queryBuilder): void
    {
        $this->cache->save($this->getQueryCacheKey($segmentId), clone $queryBuilder);
    }

    /**
     * @param int $segmentId
     *
     * @return string
     */
    private function getQueryCacheKey(int $segmentId): string
    {
        return 'segment_query_' . $segmentId;
    }

    /**
     * @param int $segmentId
     *
     * @return bool
     */
    private function isQueryRegistered(int $segmentId): bool
    {
        return isset($this->queries[$segmentId]) && $this->queries[$segmentId] > 0;
    }

    /**
     * @return bool
     */
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
