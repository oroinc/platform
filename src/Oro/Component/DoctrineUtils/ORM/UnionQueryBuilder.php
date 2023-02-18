<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\DBAL\Query\QueryException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

/**
 * This class can be used to build UNION or UNION ALL SQL query.
 */
class UnionQueryBuilder
{
    private EntityManagerInterface $em;
    private bool $unionAll;
    private string $alias;
    private array $select = [];
    /** @var Query[] */
    private array $subQueries = [];
    private ?int $firstResult = null;
    private ?int $maxResults = null;
    private ?array $orderBy = null;

    /**
     * @param EntityManagerInterface $em       The entity manager
     * @param bool                   $unionAll Whether UNION ALL should be used rather than UNION
     * @param string                 $alias    The query alias
     */
    public function __construct(EntityManagerInterface $em, bool $unionAll = true, string $alias = 'entity')
    {
        $this->em = $em;
        $this->unionAll = $unionAll;
        $this->alias = $alias;
    }

    /**
     * Constructs an instance of SqlQuery from the current specifications of the builder.
     *
     * @throws QueryException if a query builder cannot be constructed
     */
    public function getQuery(): SqlQuery
    {
        return $this->getQueryBuilder()->getQuery();
    }

    /**
     * Constructs an instance of SqlQueryBuilder from the current specifications of the builder.
     *
     * @throws QueryException if a query builder cannot be constructed
     */
    public function getQueryBuilder(): SqlQueryBuilder
    {
        if (empty($this->subQueries)) {
            throw new QueryException('At least one sub-query should be added.');
        }
        if (empty($this->select)) {
            throw new QueryException('At least one select expression should be added.');
        }

        $qb = $this->createQueryBuilder($this->em, $this->getResultSetMapping())
            ->select($this->getSelectStatement())
            ->from($this->getFromStatement(), $this->alias)
            ->setFirstResult($this->firstResult)
            ->setMaxResults($this->maxResults);
        if (!empty($this->orderBy)) {
            foreach ($this->orderBy as $sort => $direction) {
                $qb->addOrderBy($sort, $direction);
            }
        }

        return $qb;
    }

    /**
     * Sets an alias for the query.
     */
    public function setAlias(string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Gets an alias for the query.
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Sets a flag indicates whether UNION ALL or UNION query should be built.
     */
    public function setUnionAll(bool $unionAll): static
    {
        $this->unionAll = $unionAll;

        return $this;
    }

    /**
     * Indicates whether UNION ALL or UNION query is built.
     */
    public function getUnionAll(): bool
    {
        return $this->unionAll;
    }

    /**
     * Adds an item that is to be returned in the query result.
     */
    public function addSelect(string $column, string $alias, string $type = 'string'): static
    {
        $this->select[] = [$column, $alias, $type];

        return $this;
    }

    /**
     * Gets items that is to be returned in the query result.
     *
     * @return array [[column, alias, type], ...]
     */
    public function getSelect(): array
    {
        return $this->select;
    }

    /**
     * Adds a sub-query of the building UNION query.
     */
    public function addSubQuery(Query $query): static
    {
        $this->subQueries[] = $query;

        return $this;
    }

    /**
     * Gets sub-queries of the building UNION query.
     *
     * @return Query[]
     */
    public function getSubQueries(): array
    {
        return $this->subQueries;
    }

    /**
     * Sets the position of the first result to retrieve (the "offset").
     */
    public function setFirstResult(?int $firstResult): static
    {
        $this->firstResult = $firstResult;

        return $this;
    }

    /**
     * Gets the position of the first result the query object was set to retrieve (the "offset").
     * Returns NULL if {@link setFirstResult} was not applied to this QueryBuilder.
     */
    public function getFirstResult(): ?int
    {
        return $this->firstResult;
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     */
    public function setMaxResults(?int $maxResults): static
    {
        $this->maxResults = $maxResults;

        return $this;
    }

    /**
     * Gets the maximum number of results the query object was set to retrieve (the "limit").
     * Returns NULL if {@link setMaxResults} was not applied to this query builder.
     */
    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    /**
     * Adds an ordering to the query results.
     */
    public function addOrderBy(string $sort, ?string $order = null): static
    {
        $this->orderBy[$sort] = $order;

        return $this;
    }

    /**
     * Gets an ordering of to the query results.
     *
     * @return array|null [expression => direction, ...]
     */
    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    private function createQueryBuilder(EntityManagerInterface $em, Query\ResultSetMapping $rsm): SqlQueryBuilder
    {
        return new SqlQueryBuilder($em, $rsm);
    }

    private function getSelectStatement(): string
    {
        $select = [];
        $mapping = QueryUtil::parseQuery(reset($this->subQueries))->getResultSetMapping();
        foreach ($this->select as $item) {
            $select[] = sprintf(
                '%s.%s AS %s',
                $this->alias,
                ResultSetMappingUtil::getColumnNameByAlias($mapping, $item[0]),
                $item[1]
            );
        }

        return implode(', ', $select);
    }

    private function getResultSetMapping(): Query\ResultSetMapping
    {
        $rsm = ResultSetMappingUtil::createResultSetMapping($this->em->getConnection()->getDatabasePlatform());
        foreach ($this->select as $item) {
            $rsm->addScalarResult($item[1], $item[1], $item[2]);
        }

        return $rsm;
    }

    private function getFromStatement(): string
    {
        $subQueries = [];
        foreach ($this->subQueries as $subQuery) {
            $subQueries[] = '(' . QueryUtil::getExecutableSql($subQuery) . ')';
        }

        return '(' . implode($this->unionAll ? ' UNION ALL ' : ' UNION ', $subQueries) . ')';
    }
}
