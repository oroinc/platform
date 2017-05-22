<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\DBAL\Query\QueryException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

/**
 * This class can be used to build UNION or UNION ALL SQL query.
 */
class UnionQueryBuilder
{
    /** @var EntityManager */
    private $em;

    /** @var bool */
    private $unionAll;

    /** @var string */
    private $alias;

    /** @var array */
    private $select = [];

    /** @var Query[] */
    private $subQueries = [];

    /** @var int|null */
    private $firstResult;

    /** @var int|null */
    private $maxResults;

    /** @var array|null */
    private $orderBy;

    /**
     * @param EntityManager $em       The entity manager
     * @param bool          $unionAll Whether UNION ALL should be used rather than UNION
     * @param string        $alias    The query alias
     */
    public function __construct(EntityManager $em, $unionAll = true, $alias = 'entity')
    {
        $this->em = $em;
        $this->unionAll = $unionAll;
        $this->alias = $alias;
    }

    /**
     * Constructs an instance of SqlQuery from the current specifications of the builder.
     *
     * @return SqlQuery
     *
     * @throws QueryException if a query builder cannot be constructed
     */
    public function getQuery()
    {
        return $this->getQueryBuilder()->getQuery();
    }

    /**
     * Constructs an instance of SqlQueryBuilder from the current specifications of the builder.
     *
     * @return SqlQueryBuilder
     *
     * @throws QueryException if a query builder cannot be constructed
     */
    public function getQueryBuilder()
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
     *
     * @param string $alias
     *
     * @return self
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Gets an alias for the query.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Sets a flag indicates whether UNION ALL or UNION query should be built.
     *
     * @param bool $unionAll
     *
     * @return self
     */
    public function setUnionAll($unionAll)
    {
        $this->unionAll = $unionAll;

        return $this;
    }

    /**
     * Indicates whether UNION ALL or UNION query is built.
     *
     * @return bool
     */
    public function getUnionAll()
    {
        return $this->unionAll;
    }

    /**
     * Adds an item that is to be returned in the query result.
     *
     * @param string $column The alias of a column in a sub-query.
     * @param string $alias  The alias of select column.
     * @param string $type   The data type of select column.
     *
     * @return self
     */
    public function addSelect($column, $alias, $type = 'string')
    {
        $this->select[] = [$column, $alias, $type];

        return $this;
    }

    /**
     * Gets items that is to be returned in the query result.
     *
     * @return array [[column, alias, type], ...]
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * Adds a sub-query of the building UNION query.
     *
     * @param Query $query
     *
     * @return self
     */
    public function addSubQuery(Query $query)
    {
        $this->subQueries[] = $query;

        return $this;
    }

    /**
     * Gets sub-queries of the building UNION query.
     *
     * @return Query[]
     */
    public function getSubQueries()
    {
        return $this->subQueries;
    }

    /**
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param int $firstResult
     *
     * @return self
     */
    public function setFirstResult($firstResult)
    {
        $this->firstResult = $firstResult;

        return $this;
    }

    /**
     * Gets the position of the first result the query object was set to retrieve (the "offset").
     * Returns NULL if {@link setFirstResult} was not applied to this QueryBuilder.
     *
     * @return int|null
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param int $maxResults
     *
     * @return self
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;

        return $this;
    }

    /**
     * Gets the maximum number of results the query object was set to retrieve (the "limit").
     * Returns NULL if {@link setMaxResults} was not applied to this query builder.
     *
     * @return int|null
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * Adds an ordering to the query results.
     *
     * @param string $sort  The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return self
     */
    public function addOrderBy($sort, $order = null)
    {
        $this->orderBy[$sort] = $order;

        return $this;
    }

    /**
     * Gets an ordering of to the query results.
     *
     * @return array|null [expression => direction, ...]
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @param EntityManager          $em
     * @param Query\ResultSetMapping $rsm
     *
     * @return SqlQueryBuilder
     */
    protected function createQueryBuilder(EntityManager $em, Query\ResultSetMapping $rsm)
    {
        return new SqlQueryBuilder($em, $rsm);
    }

    /**
     * @return string
     */
    private function getSelectStatement()
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

    /**
     * @return Query\ResultSetMapping
     */
    private function getResultSetMapping()
    {
        $rsm = ResultSetMappingUtil::createResultSetMapping($this->em->getConnection()->getDatabasePlatform());
        foreach ($this->select as $item) {
            $rsm->addScalarResult($item[1], $item[1], $item[2]);
        }

        return $rsm;
    }

    /**
     * @return string
     */
    private function getFromStatement()
    {
        $subQueries = [];
        foreach ($this->subQueries as $subQuery) {
            $subQueries[] = '(' . QueryUtil::getExecutableSql($subQuery) . ')';
        }

        return '(' . implode($this->unionAll ? ' UNION ALL ' : ' UNION ', $subQueries) . ')';
    }
}
