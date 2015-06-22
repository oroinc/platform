<?php

namespace Oro\Bundle\BatchBundle\ORM\Query;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountWalker;

use Oro\Bundle\EntityBundle\ORM\QueryUtils;
use Oro\Bundle\EntityBundle\ORM\SqlQuery;

/**
 * Calculates total count of query records
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

            // fix of doctrine count walker bug
            // TODO revert changes when doctrine version >= 2.5 in scope of BAP-5577
            /* @var $countQuery Query */
            $countQuery = clone $query;
            $countQuery->setParameters(clone $query->getParameters());
            foreach ($query->getHints() as $name => $value) {
                $countQuery->setHint($name, $value);
            }
            if (!$countQuery->hasHint(CountWalker::HINT_DISTINCT)) {
                $countQuery->setHint(CountWalker::HINT_DISTINCT, true);
            }

            $this->appendTreeWalker($countQuery, 'Oro\Bundle\BatchBundle\ORM\Query\Walker\CountWalker');
            $countQuery->setFirstResult(null)->setMaxResults(null);

            try {
                $data   = $countQuery->getScalarResult();
                $data   = array_map('current', $data);
                $result = array_sum($data);
            } catch (NoResultException $e) {
                $result = 0;
            }
        } else {
            if ($query instanceof Query) {
                $parserResult      = QueryUtils::parseQuery($query);
                $parameterMappings = $parserResult->getParameterMappings();
                list($params, $types) = QueryUtils::processParameterMappings($query, $parameterMappings);

                $statement = $query->getEntityManager()->getConnection()->executeQuery(
                    'SELECT COUNT(*) FROM (' . $query->getSQL() . ') AS e',
                    $params,
                    $types
                );
            } elseif ($query instanceof SqlQuery) {
                $countQuery = clone $query->getQueryBuilder();
                $statement  = $countQuery->resetQueryParts()
                    ->select('COUNT(*)')
                    ->from('(' . $query->getSQL() . ')', 'e')
                    ->execute();
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Expected instance of Doctrine\ORM\Query or Oro\Bundle\EntityBundle\ORM\SqlQuery, "%s" given',
                        is_object($query) ? get_class($query) : gettype($query)
                    )
                );
            }

            $result = $statement->fetchColumn();
        }

        return $result ? (int)$result : 0;
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

    /**
     * Appends a custom tree walker to the tree walkers hint.
     *
     * @param Query  $query
     * @param string $walkerClass
     */
    private function appendTreeWalker(Query $query, $walkerClass)
    {
        $hints = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);

        if ($hints === false) {
            $hints = [];
        }

        $hints[] = $walkerClass;
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $hints);
    }
}
