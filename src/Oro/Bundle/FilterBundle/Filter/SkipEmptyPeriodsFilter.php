<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\SkipEmptyPeriodsFilterType;
use Symfony\Component\Form\FormFactoryInterface;

class SkipEmptyPeriodsFilter extends ChoiceFilter
{
    /** @var bool */
    protected $active = false;

    public function __construct(FormFactoryInterface $factory, FilterUtility $util)
    {
        parent::__construct($factory, $util);
    }

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
        if (!$data || !array_key_exists('value', $data) || !is_array($data['value'])) {
            return false;
        }

        $this->active = true;

        return true;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }
}
