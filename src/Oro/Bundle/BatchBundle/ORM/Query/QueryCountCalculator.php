<?php

namespace Oro\Bundle\BatchBundle\ORM\Query;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Oro\Component\DoctrineUtils\ORM\SqlQuery;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;

/**
 * Calculates total count of query records.
 */
class QueryCountCalculator
{
    /** @var bool|null */
    private $shouldUseWalker;

    /** @var bool|null */
    private $shouldUseDistinct;

    /**
     * Calculates the total count of records returned by the given query.
     *
     * @param Query|SqlQuery $query
     * @param bool           $useWalker
     *
     * @return int
     */
    public static function calculateCount($query, $useWalker = null)
    {
        $instance = new static();
        $instance->setUseWalker($useWalker);

        return $instance->getCount($query);
    }

    /**
     * Calculates the total count of unique records returned by the given query.
     *
     * @param Query|SqlQuery $query
     *
     * @return int
     */
    public static function calculateCountDistinct($query)
    {
        $instance = new static();
        $instance->setUseDistinct(true);

        return $instance->getCount($query);
    }

    /**
     * @param bool $value  Determines whether {@see \Doctrine\ORM\Tools\Pagination\CountWalker} should be used
     *                     or the count query should be wrapped with an additional SELECT statement.
     *                     The walker might be turned off on queries with GROUP BY statement and count select
     *                     will returns large dataset (it's only critical when more then e.g. 1000 results returned)
     *                     Another point to disable the walker is when the query has LIMIT
     *                     and you want to count results relatively to it.
     */
    public function setUseWalker($value)
    {
        $this->shouldUseWalker = $value;
    }

    /**
     * @param bool $value  Determine whether DISTINCT keyword should be used or not.
     *                     By default this keyword is used only if the source query has this keyword.
     */
    public function setUseDistinct($value)
    {
        $this->shouldUseDistinct = $value;
    }

    /**
     * Calculates total count of query records
     * Notes: this method do not make any modifications of the given query
     *
     * @param Query|SqlQuery $query
     *
     * @return int
     */
    public function getCount($query)
    {
        if ($this->useWalker($query)) {
            $result = $this->executeOrmCountQueryUsingCountWalker($query);
        } elseif (true === $this->shouldUseDistinct) {
            throw new \InvalidArgumentException(sprintf(
                'The usage of DISTINCT keyword can be forced only together with %s.',
                CountWalker::class
            ));
        } else {
            if ($query instanceof Query) {
                $statement = $this->executeOrmCountQuery($query);
            } elseif ($query instanceof SqlQuery) {
                $statement = $this->executeDbalCountQuery($query->getQueryBuilder());
            } elseif ($query instanceof SqlQueryBuilder) {
                $statement = $this->executeDbalCountQuery($query);
            } elseif ($query instanceof DbalQueryBuilder) {
                $statement = $this->executeDbalCountQuery($query);
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'Expected instance of %s, %s or %s, "%s" given.',
                    Query::class,
                    SqlQuery::class,
                    DbalQueryBuilder::class,
                    is_object($query) ? get_class($query) : gettype($query)
                ));
            }
            $result = $statement->fetchColumn();
        }

        return $result ? (int)$result : 0;
    }

    private function executeOrmCountQueryUsingCountWalker(Query $query): int
    {
        $countQuery = QueryUtil::cloneQuery($query);
        if (null !== $this->shouldUseDistinct) {
            $countQuery->setHint(CountWalker::HINT_DISTINCT, $this->shouldUseDistinct);
        } elseif (!$countQuery->hasHint(CountWalker::HINT_DISTINCT)) {
            $countQuery->setHint(CountWalker::HINT_DISTINCT, $countQuery->contains('DISTINCT'));
        }
        QueryUtil::addTreeWalker($countQuery, CountWalker::class);
        $this->unbindUnusedQueryParams($countQuery, QueryUtil::parseQuery($countQuery));
        $countQuery->setFirstResult(null)->setMaxResults(null);
        // the result-set mapping is not relevant and should be rebuilt because the query is be changed
        QueryUtil::resetResultSetMapping($countQuery);

        try {
            return array_sum(array_map('current', $countQuery->getScalarResult()));
        } catch (NoResultException $e) {
            return 0;
        }
    }

    /**
     * @param Query $query
     *
     * @return Statement
     */
    private function executeOrmCountQuery(Query $query)
    {
        $parserResult = QueryUtil::parseQuery($query);
        $sql = $parserResult->getSqlExecutor()->getSqlStatements();
        $parameterMappings = $parserResult->getParameterMappings();
        [$params, $types] = QueryUtil::processParameterMappings($query, $parameterMappings);

        return $query->getEntityManager()->getConnection()->executeQuery(
            sprintf('SELECT COUNT(*) FROM (%s) AS count_query', $sql),
            $params,
            $types
        );
    }

    /**
     * @param DbalQueryBuilder|SqlQueryBuilder $query
     *
     * @return Statement
     */
    private function executeDbalCountQuery($query)
    {
        $countQuery = clone $query;

        return $countQuery->resetQueryParts()
            ->select('COUNT(*)')
            ->from('(' . $query->getSQL() . ')', 'count_query')
            ->execute();
    }

    private function unbindUnusedQueryParams(Query $query, Query\ParserResult $parserResult): void
    {
        $parameterMappings = $parserResult->getParameterMappings();
        $parameters = $query->getParameters();
        foreach ($parameters as $key => $parameter) {
            $parameterName = $parameter->getName();
            if (!\array_key_exists($parameterName, $parameterMappings)) {
                unset($parameters[$key]);
            }
        }
        $query->setParameters($parameters);
    }

    /**
     * If flag to use walker not set manually we try to figure out if it will not brake query logic
     *
     * @param Query|SqlQuery $query
     *
     * @return bool
     */
    private function useWalker($query)
    {
        if ($query instanceof Query) {
            if (null === $this->shouldUseWalker) {
                return !$query->contains('GROUP BY') && null === $query->getMaxResults();
            }

            return $this->shouldUseWalker;
        }

        return false;
    }
}
