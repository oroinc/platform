<?php

namespace Oro\Bundle\SegmentBundle\Query;

/**
 * The container of query builders for all registered types of segments.
 */
class SegmentQueryBuilderRegistry
{
    /** @var QueryBuilderInterface[] */
    private $queryBuilders = [];

    /**
     * Registers a segment query builder for the given segment type.
     *
     * @param string                $segmentType
     * @param QueryBuilderInterface $queryBuilder
     */
    public function addQueryBuilder(string $segmentType, QueryBuilderInterface $queryBuilder): void
    {
        $this->queryBuilders[$segmentType] = $queryBuilder;
    }

    /**
     * Gets a segment query builder for the given segment type.
     *
     * @param string $segmentType
     *
     * @return QueryBuilderInterface|null
     */
    public function getQueryBuilder(string $segmentType): ?QueryBuilderInterface
    {
        return $this->queryBuilders[$segmentType] ?? null;
    }
}
