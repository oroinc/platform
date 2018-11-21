<?php

namespace Oro\Bundle\BatchBundle\ORM\Query;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Oro\Component\DoctrineUtils\ORM\SqlQuery;

/**
 * Calculates total count of query records.
 */
class QueryCountCalculator
{
    /** @var bool|null */
    private $shouldUseWalker;

    /**
     * Calculates total count of query records
     *
     * @param Query|SqlQuery $query
     * @param bool           $useWalker
     *
     * @return integer
     */
    public static function calculateCount($query, $useWalker = null)
    {
        /** @var QueryCountCalculator $instance */
        $instance = new static();

        $instance->setUseWalker($useWalker);

        return $instance->getCount($query);
    }

    /**
     * @param bool $value  Determine should CountWalker be used or wrap count query with additional select.
     *                     Walker might be turned off on queries where exists GROUP BY statement and count select
     *                     will returns large dataset(it's only critical when more then e.g. 1000 results returned)
     *                     Another point to disable it, when query has LIMIT and you want to count results
     *                     relatively to it.
     */
    public function setUseWalker($value)
    {
        $this->shouldUseWalker = $value;
    }

    /**
     * Calculates total count of query records
     * Notes: this method do not make any modifications of the given query
     *
     * @param Query|SqlQuery $query
     *
     * @return integer
     */
    public function getCount($query)
    {
        if ($this->useWalker($query)) {
            if (!$query->contains('DISTINCT')) {
                $query->setHint(CountWalker::HINT_DISTINCT, false);
            }

            $paginator = new Paginator($query);
            $paginator->setUseOutputWalkers(false);
            $result = $paginator->count();
        } else {
            if ($query instanceof Query) {
                $statement = $this->executeOrmCountQuery($query);
            } elseif ($query instanceof SqlQuery) {
                $statement = $this->executeDbalCountQuery($query->getQueryBuilder());
            } elseif ($query instanceof DbalQueryBuilder) {
                $statement = $this->executeDbalCountQuery($query);
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'Expected instance of %s, %s or %s, "%s" given',
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

    /**
     * @param Query $query
     *
     * @return Statement
     */
    private function executeOrmCountQuery(Query $query)
    {
        $parserResult = QueryUtil::parseQuery($query);
        $parameterMappings = $parserResult->getParameterMappings();
        list($params, $types) = QueryUtil::processParameterMappings($query, $parameterMappings);

        return $query->getEntityManager()->getConnection()->executeQuery(
            sprintf('SELECT COUNT(*) FROM (%s) AS count_query', $query->getSQL()),
            $params,
            $types
        );
    }

    /**
     * @param DbalQueryBuilder $query
     *
     * @return Statement
     */
    private function executeDbalCountQuery(DbalQueryBuilder $query)
    {
        $countQuery = clone $query;

        return $countQuery->resetQueryParts()
            ->select('COUNT(*)')
            ->from('(' . $query->getSQL() . ')', 'count_query')
            ->execute();
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
