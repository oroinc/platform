<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

class PreviousFilterDateRangeConverter extends FilterDateTimeRangeConverter
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
            $prevDateRange = $options[$config['converter_attributes']['dateRangeField']];
            if (isset($prevDateRange['type'])) {
                $prevDateRange = parent::getConvertedValue(
                    $widgetConfig,
                    $prevDateRange,
                    $config,
                    $options
                );
            }

            $from = $prevDateRange['start'];
            $to   = $prevDateRange['end'];

            $interval        = $from->diff($to);
            $fromDate        = clone $from;
            $result['start'] = $fromDate->sub($interval);
            $result['end']   = clone $from;
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
