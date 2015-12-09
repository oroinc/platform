<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;

class SqlQueryBuilder
{
    /** @var EntityManager */
    protected $em;

    /** @var DbalQueryBuilder */
    protected $qb;

    /** @var ResultSetMapping */
    protected $rsm;

    /**
     * @param EntityManager    $em
     * @param ResultSetMapping $rsm
     */
    public function __construct(EntityManager $em, ResultSetMapping $rsm)
    {
        $this->em  = $em;
        $this->qb  = $em->getConnection()->createQueryBuilder();
        $this->rsm = $rsm;
    }

    /**
     * Constructs a SqlQuery instance from the current specifications of the builder.
     *
     * @return SqlQuery
     */
    public function getQuery()
    {
        $query = new SqlQuery($this->em);

        $query->setQueryBuilder($this->qb);
        $query->setResultSetMapping($this->rsm);

        return $query;
    }

    /**
     * Gets an ExpressionBuilder used for object-oriented construction of query expressions.
     *
     * @return \Doctrine\DBAL\Query\Expression\ExpressionBuilder
     */
    public function expr()
    {
        return $this->qb->expr();
    }

    /**
     * Gets the type of the currently built query.
     *
     * @return integer
     */
    public function getType()
    {
        return $this->qb->getType();
    }

    /**
     * Gets the associated DBAL Connection for this query builder.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->qb->getConnection();
    }

    /**
     * Gets the state of this query builder instance.
     *
     * @return integer Either Doctrine\DBAL\Query\QueryBuilder::STATE_DIRTY
     * or Doctrine\DBAL\Query\QueryBuilder::STATE_CLEAN.
     */
    public function getState()
    {
        return $this->qb->getState();
    }

    /**
     * Gets the complete SQL string formed by the current specifications of this query builder.
     *
     * @return string The SQL query string.
     */
    public function getSQL()
    {
        return $this->qb->getSQL();
    }

    /**
     * Sets a query parameter for the query being constructed.
     *
     * @param string|integer $key   The parameter position or name.
     * @param mixed          $value The parameter value.
     * @param string|null    $type  One of the PDO::PARAM_* constants.
     *
     * @return self
     */
    public function setParameter($key, $value, $type = null)
    {
        $this->qb->setParameter($key, $value, $type);

        return $this;
    }

    /**
     * Sets a collection of query parameters for the query being constructed.
     *
     * @param array $params The query parameters to set.
     * @param array $types  The query parameters types to set.
     *
     * @return self
     */
    public function setParameters(array $params, array $types = [])
    {
        $this->qb->setParameters($params, $types);

        return $this;
    }

    /**
     * Gets all defined query parameters for the query being constructed.
     *
     * @return array The currently defined query parameters.
     */
    public function getParameters()
    {
        return $this->qb->getParameters();
    }

    /**
     * Gets a (previously set) query parameter of the query being constructed.
     *
     * @param mixed $key The key (index or name) of the bound parameter.
     *
     * @return mixed The value of the bound parameter.
     */
    public function getParameter($key)
    {
        return $this->qb->getParameter($key);
    }

    /**
     * Creates a new named parameter and bind the value $value to it.
     *
     * @param mixed  $value
     * @param mixed  $type
     * @param string $placeHolder The name to bind with. The string must start with a colon ':'.
     *
     * @return string the placeholder name used.
     */
    public function createNamedParameter($value, $type = \PDO::PARAM_STR, $placeHolder = null)
    {
        return $this->qb->createNamedParameter($value, $type, $placeHolder);
    }

    /**
     * Creates a new positional parameter and bind the given value to it.
     *
     * @param mixed   $value
     * @param integer $type
     *
     * @return string
     */
    public function createPositionalParameter($value, $type = \PDO::PARAM_STR)
    {
        return $this->qb->createPositionalParameter($value, $type);
    }

    /**
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param integer $firstResult The first result to return.
     *
     * @return self
     */
    public function setFirstResult($firstResult)
    {
        $this->qb->setFirstResult($firstResult);

        return $this;
    }

    /**
     * Gets the position of the first result the query object was set to retrieve (the "offset").
     * Returns NULL if {@link setFirstResult} was not applied to this query builder.
     *
     * @return integer The position of the first result.
     */
    public function getFirstResult()
    {
        return $this->qb->getFirstResult();
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param integer $maxResults The maximum number of results to retrieve.
     *
     * @return self
     */
    public function setMaxResults($maxResults)
    {
        $this->qb->setMaxResults($maxResults);

        return $this;
    }

    /**
     * Gets the maximum number of results the query object was set to retrieve (the "limit").
     * Returns NULL if {@link setMaxResults} was not applied to this query builder.
     *
     * @return integer The maximum number of results.
     */
    public function getMaxResults()
    {
        return $this->qb->getMaxResults();
    }

    /**
     * Either appends to or replaces a single, generic query part.
     *
     * The available parts are: 'select', 'from', 'set', 'where',
     * 'groupBy', 'having' and 'orderBy'.
     *
     * @param string  $sqlPartName
     * @param string  $sqlPart
     * @param boolean $append
     *
     * @return self
     */
    public function add($sqlPartName, $sqlPart, $append = false)
    {
        $this->qb->add($sqlPartName, $sqlPart, $append);

        return $this;
    }

    /**
     * Specifies an item that is to be returned in the query result.
     * Replaces any previously specified selections, if any.
     *
     * @param mixed $select The selection expressions.
     *
     * @return self
     */
    public function select($select = null)
    {
        $this->qb->select($select);

        return $this;
    }

    /**
     * Adds an item that is to be returned in the query result.
     *
     * @param mixed $select The selection expression.
     *
     * @return self
     */
    public function addSelect($select = null)
    {
        $this->qb->addSelect($select);

        return $this;
    }

    /**
     * Turns the query being built into a bulk delete query that ranges over
     * a certain table.
     *
     * @param string $delete The table whose rows are subject to the deletion.
     * @param string $alias  The table alias used in the constructed query.
     *
     * @return self
     */
    public function delete($delete = null, $alias = null)
    {
        $this->qb->delete($delete, $alias);

        return $this;
    }

    /**
     * Turns the query being built into a bulk update query that ranges over
     * a certain table
     *
     * @param string $update The table whose rows are subject to the update.
     * @param string $alias  The table alias used in the constructed query.
     *
     * @return self
     */
    public function update($update = null, $alias = null)
    {
        $this->qb->update($update, $alias);

        return $this;
    }

    /**
     * Creates and adds a query root corresponding to the table identified by the
     * given alias, forming a cartesian product with any existing query roots.
     *
     * @param string $from  The table.
     * @param string $alias The alias of the table.
     *
     * @return self
     */
    public function from($from, $alias)
    {
        $this->qb->from($from, $alias);

        return $this;
    }

    /**
     * Creates and adds a join to the query.
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return self
     */
    public function join($fromAlias, $join, $alias, $condition = null)
    {
        $this->qb->join($fromAlias, $join, $alias, $condition);

        return $this;
    }

    /**
     * Creates and adds a join to the query.
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return self
     */
    public function innerJoin($fromAlias, $join, $alias, $condition = null)
    {
        $this->qb->innerJoin($fromAlias, $join, $alias, $condition);

        return $this;
    }

    /**
     * Creates and adds a left join to the query.
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return self
     */
    public function leftJoin($fromAlias, $join, $alias, $condition = null)
    {
        $this->qb->leftJoin($fromAlias, $join, $alias, $condition);

        return $this;
    }

    /**
     * Creates and adds a right join to the query.
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return self
     */
    public function rightJoin($fromAlias, $join, $alias, $condition = null)
    {
        $this->qb->rightJoin($fromAlias, $join, $alias, $condition);

        return $this;
    }

    /**
     * Sets a new value for a column in a bulk update query.
     *
     * @param string $key   The column to set.
     * @param string $value The value, expression, placeholder, etc.
     *
     * @return self
     */
    public function set($key, $value)
    {
        $this->qb->set($key, $value);

        return $this;
    }

    /**
     * Specifies one or more restrictions to the query result.
     * Replaces any previously specified restrictions, if any.
     *
     * @param mixed $predicates The restriction predicates.
     *
     * @return self
     */
    public function where($predicates)
    {
        $this->qb->where($predicates);

        return $this;
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * conjunction with any previously specified restrictions.
     *
     * @param mixed $where The query restrictions.
     *
     * @return self
     *
     * @see where()
     */
    public function andWhere($where)
    {
        $this->qb->andWhere($where);

        return $this;
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * disjunction with any previously specified restrictions.
     *
     * @param mixed $where The WHERE statement.
     *
     * @return self
     *
     * @see where()
     */
    public function orWhere($where)
    {
        $this->qb->orWhere($where);

        return $this;
    }

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * @param mixed $groupBy The grouping expression.
     *
     * @return self
     */
    public function groupBy($groupBy)
    {
        $this->qb->groupBy($groupBy);

        return $this;
    }


    /**
     * Adds a grouping expression to the query.
     *
     * @param mixed $groupBy The grouping expression.
     *
     * @return self
     */
    public function addGroupBy($groupBy)
    {
        $this->qb->addGroupBy($groupBy);

        return $this;
    }

    /**
     * Specifies a restriction over the groups of the query.
     * Replaces any previous having restrictions, if any.
     *
     * @param mixed $having The restriction over the groups.
     *
     * @return self
     */
    public function having($having)
    {
        $this->qb->having($having);

        return $this;
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * conjunction with any existing having restrictions.
     *
     * @param mixed $having The restriction to append.
     *
     * @return self
     */
    public function andHaving($having)
    {
        $this->qb->andHaving($having);

        return $this;
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * disjunction with any existing having restrictions.
     *
     * @param mixed $having The restriction to add.
     *
     * @return self
     */
    public function orHaving($having)
    {
        $this->qb->orHaving($having);

        return $this;
    }

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param string $sort  The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return self
     */
    public function orderBy($sort, $order = null)
    {
        $this->qb->orderBy($sort, $order);

        return $this;
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
        $this->qb->addOrderBy($sort, $order);

        return $this;
    }

    /**
     * Gets a query part by its name.
     *
     * @param string $queryPartName
     *
     * @return mixed
     */
    public function getQueryPart($queryPartName)
    {
        return $this->qb->getQueryPart($queryPartName);
    }

    /**
     * Gets all query parts.
     *
     * @return array
     */
    public function getQueryParts()
    {
        return $this->qb->getQueryParts();
    }

    /**
     * Resets SQL parts.
     *
     * @param array|null $queryPartNames
     *
     * @return self
     */
    public function resetQueryParts($queryPartNames = null)
    {
        $this->qb->resetQueryParts($queryPartNames);

        return $this;
    }

    /**
     * Resets a single SQL part.
     *
     * @param string $queryPartName
     *
     * @return self
     */
    public function resetQueryPart($queryPartName)
    {
        $this->qb->resetQueryPart($queryPartName);

        return $this;
    }

    /**
     * Deep clone of all expression objects in the SQL parts.
     *
     * @return void
     */
    public function __clone()
    {
        $this->qb = clone $this->qb;
    }

    /**
     * Gets a string representation of this query builder which corresponds to
     * the final SQL query being constructed.
     *
     * @return string The string representation of this query builder.
     */
    public function __toString()
    {
        return $this->getSQL();
    }
}
