<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\SkipEmptyPeriodsFilterType;

class SkipEmptyPeriodsFilter extends ChoiceFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return SkipEmptyPeriodsFilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (is_array($data) && $data['value']) {
            return false;
        }

        /** @var OrmFilterDatasourceAdapter $ds */
        $qb = $ds->getQueryBuilder();
        $qb->resetDQLPart('where');

        return true;
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    protected function parseData($data)
    {
        if (is_array($data) && !$data['value']) {
            $data['value'] = SkipEmptyPeriodsFilterType::TYPE_NO;
        }

        return $data;
    }
}
