<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Doctrine\ORM\QueryBuilder;

/**
 * The base context for classes that convert a query definition created by the query designer to an ORM query builder.
 */
class QueryBuilderGroupingOrmQueryConverterContext extends GroupingOrmQueryConverterContext
{
    /** @var QueryBuilder */
    private $qb;

    /** @var string */
    private $rootEntityAlias;

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        parent::reset();
        $this->qb = null;
        $this->rootEntityAlias = null;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
    }

    /**
     * @param QueryBuilder $qb
     */
    public function setQueryBuilder(QueryBuilder $qb): void
    {
        $this->qb = $qb;
    }

    /**
     * @return string|null
     */
    public function getRootEntityAlias(): ?string
    {
        return $this->rootEntityAlias;
    }

    /**
     * @param string $rootEntityAlias
     */
    public function setRootEntityAlias(string $rootEntityAlias): void
    {
        $this->rootEntityAlias = $rootEntityAlias;
    }
}
