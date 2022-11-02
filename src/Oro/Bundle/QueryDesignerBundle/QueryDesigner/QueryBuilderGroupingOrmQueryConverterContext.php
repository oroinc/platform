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

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
    }

    public function setQueryBuilder(QueryBuilder $qb): void
    {
        $this->qb = $qb;
    }

    public function getRootEntityAlias(): ?string
    {
        return $this->rootEntityAlias;
    }

    public function setRootEntityAlias(string $rootEntityAlias): void
    {
        $this->rootEntityAlias = $rootEntityAlias;
    }
}
