<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;

/**
 * The dashboard widget configuration converter for enter a previous date interval.
 */
class PreviousFilterDateRangeConverter extends FilterDateRangeConverter
{
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function getConvertedValue(
        array $widgetConfig,
        mixed $value = null,
        array $config = [],
        array $options = []
    ): mixed {
        $result = [];

        if ($value || (null === $value && true === $config['converter_attributes']['default_checked'])) {
            if (!isset($config['converter_attributes']['dateRangeField'])) {
                throw new InvalidConfigurationException(
                    'Previous date range configuration parameter should have dateRangeField attribute'
                );
            }
            $currentDateRange = $options[$config['converter_attributes']['dateRangeField']];

            if (isset($currentDateRange['value'])) {
                $currentDateRange = parent::getConvertedValue(
                    $widgetConfig,
                    $currentDateRange,
                    $config,
                    $options
                );
            }

            if (
                AbstractDateFilterType::TYPE_LESS_THAN !== $currentDateRange['type']
                && $currentDateRange['start']
                && $currentDateRange['end']
            ) {
                /** @var \DateTime $start */
                $start = clone $currentDateRange['start'];
                /** @var \DateTime $end */
                $end = clone $currentDateRange['end'];
                if (\in_array(
                    $currentDateRange['type'],
                    [
                        AbstractDateFilterType::TYPE_THIS_MONTH,
                        AbstractDateFilterType::TYPE_THIS_QUARTER,
                        AbstractDateFilterType::TYPE_THIS_YEAR
                    ],
                    true
                )) {
                    if (AbstractDateFilterType::TYPE_THIS_MONTH == $currentDateRange['type']) {
                        $start->modify('first day of previous month');
                        $end->modify('last day of previous month');
                    } elseif (AbstractDateFilterType::TYPE_THIS_YEAR == $currentDateRange['type']) {
                        $start->modify('first day of previous year');
                        $end->modify('last day of previous year');
                    } elseif (AbstractDateFilterType::TYPE_THIS_QUARTER == $currentDateRange['type']) {
                        $start->modify('first day of - 3 month');
                        $end->modify('last day of - 3 month');
                    }
                } else {
                    // shift to previous range
                    $interval = $start->diff($end);
                    $start = $start->sub($interval);
                    $end = $end->sub($interval);
                }

                $result['start'] = $start;
                $result['end'] = $end;
                $type = $currentDateRange['type'] ?? AbstractDateFilterType::TYPE_BETWEEN;
                $result['type'] = $type;
            }
        }

        return $result;
    }

    #[\Override]
    public function getViewValue(mixed $value): mixed
    {
        if (!empty($value)) {
            return parent::getViewValue($value);
        }

        return [];
    }

    #[\Override]
    public function getFormValue(array $config, mixed $value): mixed
    {
        if (null === $value && true === $config['converter_attributes']['default_checked']) {
            return true;
        }

        return parent::getFormValue($config, $value);
    }
}
