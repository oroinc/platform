<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Component\Config\Resolver\SystemAwareResolver;

class FilterDateTimeRangeConverter extends ConfigValueConverterAbstract
{
    /** @var FilterDateRangeConverter */
    protected $converter;

    /** @var SystemAwareResolver */
    protected $resolver;

    /** @var DateHelper */
    protected $dateHelper;

    /**
     * @param FilterDateRangeConverter $converter
     * @param SystemAwareResolver      $resolver
     * @param DateHelper               $dateHelper
     */
    public function __construct(
        FilterDateRangeConverter $converter,
        SystemAwareResolver $resolver,
        DateHelper $dateHelper
    ) {
        $this->converter  = $converter;
        $this->resolver   = $resolver;
        $this->dateHelper = $dateHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewValue($value)
    {
        $convertedValue = $this->converter->getConvertedValue([], $value);

        return $this->converter->getViewValue($convertedValue);
    }

    /**
     * {@inheritdoc}
     */
    public function getConvertedValue(array $widgetConfig, $value = null, array $config = [], array $options = [])
    {
        if (null === $value &&
            isset($config['converter_attributes']['default_selected']) &&
            is_array($config['converter_attributes']['default_selected'])
        ) {
            $default = $this->getDefaultValues($config['converter_attributes']['default_selected']);

            return $default;
        }
        $value['part'] = DateModifierInterface::PART_VALUE;
        if (!isset($value['value']['start']) && !isset($value['value']['end'])) {
            /** @var \DateTime $start */
            /** @var \DateTime $end */
            list($start, $end) = $this->dateHelper->getDateTimeInterval();

            $value['value']['start'] = $start->format('Y-m-d H:i:s');
            $value['value']['end']   = $end->format('Y-m-d H:i:s');
        }

        if (isset($value['value']['start']) && isset($value['value']['end'])) {
            if ($value['value']['start'] > $value['value']['end']) {
                $end = $value['value']['end'];
                $value['value']['end'] = $value['value']['start'];
                $value['value']['start'] = $end;
            }
        }

        return parent::getConvertedValue($widgetConfig, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormValue(array $converterAttributes, $value)
    {
        if (null === $value &&
            isset($converterAttributes['converter_attributes']['default_selected']) &&
            is_array($converterAttributes['converter_attributes']['default_selected'])
        ) {
            $default = $this->getDefaultValues($converterAttributes['converter_attributes']['default_selected']);

            return $default;
        }

        return parent::getFormValue($converterAttributes, $value);
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function getDefaultValues(array $config)
    {
        $default = $this->resolver->resolve($config);
        if (isset($default['value']['start'])) {
            $default['value']['start'] = str_replace(' ', '', $default['value']['start']);
        }
        if (isset($default['value']['end'])) {
            $default['value']['end'] = str_replace(' ', '', $default['value']['end']);

            return $default;
        }

        return $default;
    }
}
