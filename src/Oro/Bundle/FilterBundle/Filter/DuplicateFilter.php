<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Component\Exception\UnexpectedTypeException;

/**
 * Provides a functionality to filter duplicated entities.
 */
class DuplicateFilter extends BooleanFilter
{
    /**
     * {@inheritDoc}
     */
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName
    ) {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, OrmFilterDatasourceAdapter::class);
        }

        $operator = $comparisonType === BooleanFilterType::TYPE_YES ? '>' : '=';

        $qb = clone $ds->getQueryBuilder();
        $qb
            ->resetDqlPart('orderBy')
            ->resetDqlPart('where')
            ->select($fieldName)
            ->groupBy($fieldName)
            ->having(sprintf('COUNT(%s) %s 1', $fieldName, $operator));
        [$dql] = $this->createDqlWithReplacedAliases($ds, $qb);

        return $ds->expr()->in($fieldName, $dql);
    }
}
