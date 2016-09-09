<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;

class PreviousFilterDateRangeConverter extends FilterDateRangeConverter
{
    /**
     * {@inheritdoc}
     */
    public function getConvertedValue(array $widgetConfig, $value = null, array $config = [], array $options = [])
    {
        $result = [];

        if (($value === null && $config['converter_attributes']['default_checked'] === true) || $value) {
            if (!isset($config['converter_attributes']['dateRangeField'])) {
                throw new \Exception(
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

            if ($currentDateRange['type'] !== AbstractDateFilterType::TYPE_LESS_THAN
                && $currentDateRange['start']
                && $currentDateRange['end']
            ) {
                if (in_array(
                    $currentDateRange['type'],
                    [
                        AbstractDateFilterType::TYPE_THIS_MONTH,
                        AbstractDateFilterType::TYPE_THIS_QUARTER,
                        AbstractDateFilterType::TYPE_THIS_YEAR
                    ],
                    true
                )) {
                    /** @var \DateTime $start */
                    $start = clone $currentDateRange['start'];
                    /** @var \DateTime $end */
                    $end = clone $currentDateRange['end'];

                    if ($currentDateRange['type'] == AbstractDateFilterType::TYPE_THIS_MONTH) {
                        $start->modify('first day of previous month');
                        $end->modify('last day of previous month');
                    } elseif ($currentDateRange['type'] == AbstractDateFilterType::TYPE_THIS_YEAR) {
                        $start->modify('first day of previous year');
                        $end->modify('last day of previous year');
                    } elseif ($currentDateRange['type'] == AbstractDateFilterType::TYPE_THIS_QUARTER) {
                        $start->modify('first day of - 3 month');
                        $end->modify('last day of - 3 month');
                    }
                } else {
                    list($start, $end) = $this->dateHelper->getPreviousDateTimeInterval(
                        $currentDateRange['start'],
                        $currentDateRange['end']
                    );
                }

                $result['start'] = $start;
                $result['end']   = $end;
                $type            = AbstractDateFilterType::TYPE_BETWEEN;
                if (isset($currentDateRange['type'])) {
                    $type = $currentDateRange['type'];
                }
                $result['type'] = $type;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewValue($value)
    {
        if (!empty($value)) {
            return parent::getViewValue($value);
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormValue(array $config, $value)
    {
        if ($value === null && $config['converter_attributes']['default_checked'] === true) {
            return true;
        }

        return parent::getFormValue($config, $value);
    }
}
