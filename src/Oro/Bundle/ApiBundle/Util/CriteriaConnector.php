<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Bundle\ApiBundle\Collection\Criteria;

class CriteriaConnector
{
    /** @var CriteriaNormalizer */
    protected $criteriaNormalizer;

    /** @var CriteriaPlaceholdersResolver */
    protected $placeholdersResolver;

    /**
     * @param CriteriaNormalizer           $criteriaNormalizer
     * @param CriteriaPlaceholdersResolver $placeholdersResolver
     */
    public function __construct(
        CriteriaNormalizer $criteriaNormalizer,
        CriteriaPlaceholdersResolver $placeholdersResolver
    ) {
        $this->criteriaNormalizer = $criteriaNormalizer;
        $this->placeholdersResolver = $placeholdersResolver;
    }

    /**
     * Adds the given criteria to the query builder.
     *
     * @param QueryBuilder $qb
     * @param Criteria     $criteria
     */
    public function applyCriteria(QueryBuilder $qb, Criteria $criteria)
    {
        $this->criteriaNormalizer->normalizeCriteria($criteria);
        $this->placeholdersResolver->resolvePlaceholders($criteria, QueryUtils::getSingleRootAlias($qb));

        $joins = $criteria->getJoins();
        if (!empty($joins)) {
            foreach ($joins as $join) {
                $method = strtolower($join->getJoinType()) . 'Join';
                $qb->{$method}(
                    $join->getJoin(),
                    $join->getAlias(),
                    $join->getConditionType(),
                    $join->getCondition(),
                    $join->getIndexBy()
                );
            }
        }
        $qb->addCriteria($criteria);
    }
}
