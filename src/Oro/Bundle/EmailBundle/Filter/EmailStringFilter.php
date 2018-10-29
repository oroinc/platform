<?php

namespace Oro\Bundle\EmailBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\StringFilter;

/**
 * Fictive string filter which is used in email grids. Has dummy ::apply() method because of performance issues of
 * regular string filter on email grids. Method ::applyAndGetExpression() is explicitly used in
 * Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory to apply filter to the datagrid query.
 */
class EmailStringFilter extends StringFilter
{
    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        /** @var $ds OrmFilterDatasourceAdapter $data */
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        return true;
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param array $data
     *
     * @return array|null Returns the filter query expression and its parameters. Returns null if data cannot be parsed.
     */
    public function applyAndGetExpression(FilterDatasourceAdapterInterface $ds, $data): ?array
    {
        $parsedData = $this->parseData($data);
        if (!$parsedData) {
            return null;
        }

        $sourceParametersCollection = clone $ds->getQueryBuilder()->getParameters();
        $sourceParameters = $sourceParametersCollection->toArray();

        $expression = $this->buildExpr($ds, $parsedData['type'], $this->getDataFieldName(), $parsedData);
        $parameters = array_diff(
            $ds->getQueryBuilder()->getParameters()->toArray(),
            $sourceParameters
        );

        $ds->getQueryBuilder()->setParameters($sourceParametersCollection);

        return [$expression, $parameters];
    }
}
