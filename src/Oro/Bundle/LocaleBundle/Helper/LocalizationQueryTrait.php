<?php

namespace Oro\Bundle\LocaleBundle\Helper;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides query building utilities for joining localized fallback values.
 *
 * This trait offers helper methods for constructing Doctrine {@see QueryBuilder} queries that join
 * {@see LocalizedFallbackValue} entities. It simplifies the process of adding localized data to queries
 * by handling the join logic and selecting the appropriate fallback values (those with null
 * localization, representing default/system-wide values). The trait supports both `INNER_JOIN`
 * and `LEFT_JOIN` strategies, allowing flexible query construction for different use cases.
 */
trait LocalizationQueryTrait
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param $join
     * @param $joinAlias
     * @param $fieldAlias
     * @param string $joinType
     * @return QueryBuilder
     */
    public function joinDefaultLocalizedValue(
        QueryBuilder $queryBuilder,
        $join,
        $joinAlias,
        $fieldAlias,
        $joinType = Join::INNER_JOIN
    ) {
        QueryBuilderUtil::checkIdentifier($joinAlias);
        QueryBuilderUtil::checkIdentifier($fieldAlias);
        QueryBuilderUtil::checkField($join);

        if ($joinType == Join::INNER_JOIN) {
            return $queryBuilder
                ->addSelect(sprintf('%s.string as %s', $joinAlias, $fieldAlias))
                ->innerJoin($join, $joinAlias, Join::WITH, sprintf('%s.localization IS NULL', $joinAlias));
        }

        return $queryBuilder
            ->addSelect(sprintf('%s.string as %s', $joinAlias, $fieldAlias))
            ->leftJoin($join, $joinAlias, Join::WITH, sprintf('%s.localization IS NULL', $joinAlias));
    }
}
