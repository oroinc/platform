<?php

namespace Oro\Bundle\LocaleBundle\Helper;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

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
