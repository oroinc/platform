<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\SkipEmptyPeriodsFilterType;

class SkipEmptyPeriodsFilter extends ChoiceFilter
{
    const NAME = 'skip_empty_periods';

    /** @var array */
    protected $config = [];

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
        $data = $this->parseData($data);
        $this->resolveConfiguration();

        /** @var OrmFilterDatasourceAdapter $ds */
        $qb = $ds->getQueryBuilder();

        if (boolval($data['value'])) {
            $qb->andWhere(
                sprintf('%s IS NOT NULL', $this->config['not_nullable_field'])
            );
        }

        return true;
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    protected function parseData($data)
    {
        if (is_array($data) && is_null($data['value'])) {
            $data['value'] = true;
        }

        return $data;
    }

    /**
     * Resolve options from configuration or constants.
     */
    private function resolveConfiguration()
    {
        $this->config['not_nullable_field'] = $this->get('not_nullable_field');
    }
}
