<?php

namespace Oro\Bundle\DashboardBundle\Provider;

abstract class ConfigValueConverterAbstract
{
    /**
     * Returns converted value
     *
     * @param array $widgetConfig
     * @param mixed $value
     * @return mixed
     */
    public function getConvertedValue(array $widgetConfig, $value = null)
    {
        return $value;
    }

    /**
     * Returns string representation of converted value
     *
     * @param mixed $value
     * @return string
     */
    public function getViewValue($value)
    {
        return (string)$value;
    }
}
