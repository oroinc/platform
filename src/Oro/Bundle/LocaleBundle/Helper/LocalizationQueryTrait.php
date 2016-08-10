<?php

namespace Oro\Bundle\LocaleBundle\Helper;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

trait LocalizationQueryTrait
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string $join
     * @param string $joinAlias
     * @param string $fieldAlias
     * @return QueryBuilder
     */
    public function joinDefaultLocalizedValue(QueryBuilder $queryBuilder, $join, $joinAlias, $fieldAlias)
    {
        $queryBuilder
            ->addSelect(sprintf('%s.string as %s', $joinAlias, $fieldAlias))
            ->innerJoin($join, $joinAlias, Join::WITH, sprintf('%s.localization IS NULL', $joinAlias));

        return $queryBuilder;
    }
}
