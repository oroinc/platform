<?php

namespace Oro\Bundle\SegmentBundle\Query;

class SegmentQueryBuilderRegistry
{
    /** @var QueryBuilderInterface[] */
    private $queryBuilders = [];

    /**
     * Registers a query builder for a given segment type.
     *
     * @param string                $type
     * @param QueryBuilderInterface $queryBuilder
     */
    public function addQueryBuilder($type, QueryBuilderInterface $queryBuilder)
    {
        $this->queryBuilders[$type] = $queryBuilder;
    }

    /**
     * Returns a data transformer for a given data type.
     *
     * @param string $type
     *
     * @return QueryBuilderInterface|null
     */
    public function getQueryBuilder($type)
    {
        return isset($this->queryBuilders[$type]) ? $this->queryBuilders[$type] : null;
    }
}
