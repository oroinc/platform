<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Component\Config\Resolver\SystemAwareResolver;

/**
 * The dashboard widget configuration converter for enter a date time range value.
 */
class FilterDateTimeRangeConverter extends ConfigValueConverterAbstract
{
    public function __construct(
        protected FilterDateRangeConverter $converter,
        protected SystemAwareResolver $resolver,
        protected DateHelper $dateHelper
    ) {
    }

    #[\Override]
    public function getViewValue(mixed $value): mixed
    {
        return $this->converter->getViewValue($this->converter->getConvertedValue([], $value));
    }

    #[\Override]
    public function getConvertedValue(
        array $widgetConfig,
        mixed $value = null,
        array $config = [],
        array $options = []
    ): mixed {
        if (null === $value
            && isset($config['converter_attributes']['default_selected'])
            && \is_array($config['converter_attributes']['default_selected'])
        ) {
            return $this->getDefaultValues($config['converter_attributes']['default_selected']);
        }
        $value['part'] = DateModifierInterface::PART_VALUE;
        if (!isset($value['value']['start']) && !isset($value['value']['end'])) {
            /** @var \DateTime $start */
            /** @var \DateTime $end */
            [$start, $end] = $this->dateHelper->getDateTimeInterval();

            $value['value']['start'] = $start->format('Y-m-d H:i:s');
            $value['value']['end'] = $end->format('Y-m-d H:i:s');
        }

        if (isset($value['value']['start'], $value['value']['end'])
            && $value['value']['start'] > $value['value']['end']
        ) {
            $end = $value['value']['end'];
            $value['value']['end'] = $value['value']['start'];
            $value['value']['start'] = $end;
        }

        return parent::getConvertedValue($widgetConfig, $value);
    }

    #[\Override]
    public function getFormValue(array $config, mixed $value): mixed
    {
        if (null === $value
            && isset($config['converter_attributes']['default_selected'])
            && \is_array($config['converter_attributes']['default_selected'])
        ) {
            return $this->getDefaultValues($config['converter_attributes']['default_selected']);
        }

        return parent::getFormValue($config, $value);
    }

    protected function getDefaultValues(array $config): array
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
