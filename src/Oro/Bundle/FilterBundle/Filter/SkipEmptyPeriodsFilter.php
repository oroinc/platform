<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;

class SkipEmptyPeriodsFilter extends ChoiceFilter
{
    const NAME = 'skip_empty_periods';

    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'select';

        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
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
