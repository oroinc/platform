<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;

/**
 * Filter for skipping empty periods in date range filtering.
 *
 * This filter extends {@see ChoiceFilter} to provide specialized handling for filtering
 * records based on whether they have values in a specific date field. When enabled,
 * it filters to show only records with non-null values in the target field. When
 * disabled and other filters are present, it adds an `OR` condition to include records
 * with null values, effectively allowing empty periods to be included in the results.
 */
class SkipEmptyPeriodsFilter extends ChoiceFilter
{
    const NAME = 'skip_empty_periods';

    #[\Override]
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'select';

        parent::init($name, $params);
    }

    #[\Override]
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $value = true;
        if (isset($data['value'])) {
            $value = (bool)reset($data['value']);
        }

        $fieldName = $this->getDataFieldName();

        /** @var OrmFilterDatasourceAdapter $ds */
        if ($value) {
            $this->applyFilterToClause($ds, $ds->expr()->isNotNull($fieldName));
        } elseif ($ds->getQueryBuilder()->getDQLPart('where')) {
            $this->applyFilterToClause($ds, $ds->expr()->isNull($fieldName), FilterUtility::CONDITION_OR);
        }

        return true;
    }
}
