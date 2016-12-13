<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;

class DuplicateFilter extends BooleanFilter
{
    /**
     * Build an expression used to filter data
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param int                              $comparisonType 0 to compare with false, 1 to compare with true
     * @param string                           $fieldName
     * @return string
     */
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName
    ) {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            throw new \InvalidArgumentException(sprintf(
                '"Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter" expected but "%s" given.',
                get_class($ds)
            ));
        }

        $operator = $comparisonType === BooleanFilterType::TYPE_YES ? '>' : '=';

        $qb = clone $ds->getQueryBuilder();
        $qb
            ->resetDqlPart('orderBy')
            ->resetDqlPart('where')
            ->select($fieldName)
            ->groupBy($fieldName)
            ->having(sprintf('COUNT(%s) %s 1', $fieldName, $operator));
        list($dql) = $this->createDQLWithReplacedAliases($ds, $qb);

        return $ds->expr()->in($fieldName, $dql);
    }
}
