<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

class PreviousFilterDateRangeConverter extends FilterDateTimeRangeConverter
{
    /**
     * @inheritdoc
     */
    public function getConvertedValue(array $widgetConfig, $value = null, $converterAttributes = [], $options = [])
    {
        $result = [];

        if ($value) {
            if (!isset($converterAttributes['dateRangeField'])) {
                throw new \Exception('Previus date range configuration parameter should have dateRangeField attribute');
            }
            $prevDateRange = $options[$converterAttributes['dateRangeField']];
            if (isset($prevDateRange['type'])) {
                $prevDateRange = parent::getConvertedValue($widgetConfig, $prevDateRange, $converterAttributes, $options);
            }

            $from = $prevDateRange['start'];
            $to = $prevDateRange['end'];

            $interval = $from->diff($to);
            $fromDate = clone $from;
            $result['start'] = $fromDate->sub($interval);
            $result['end'] = clone $from;
        }

        return $result;
    }
}
