<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\SkipEmptyPeriodsFilterType;
use Oro\Bundle\ProductBundle\Event\EmptyPeriodsConfigurationEvent;

class SkipEmptyPeriodsFilter extends ChoiceFilter
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param FormFactoryInterface $factory
     * @param FilterUtility $util
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($factory, $util);
        $this->eventDispatcher = $eventDispatcher;
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

        $event = new EmptyPeriodsConfigurationEvent([]);
        $this->eventDispatcher->dispatch($event::NAME, $event);

        return true;
    }
}
