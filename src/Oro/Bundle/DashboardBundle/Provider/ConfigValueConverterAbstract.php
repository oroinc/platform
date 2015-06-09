<?php

namespace Oro\Bundle\DashboardBundle\Provider;

abstract class ConfigValueConverterAbstract
{
    /**
     * Returns converted value
     *
     * @param array $widgetConfig Widget configuration
     * @param mixed $value        Value needs to be converted
     * @param array $config       Field configuration
     * @param array $options      Widget options
     *
     * @return null|mixed
     */
    public function getConvertedValue(array $widgetConfig, $value = null, array $config = [], array $options = [])
    {
        return $value;
    }

    /**
     * Returns string representation of converted value
     *
     * @param mixed $value
     *
     * @return string
     */
    public function getViewValue($value)
    {
        return (string)$value;
    }

    /**
     * Returns form value
     *
     * @param array $converterAttributes
     * @param mixed $value
     *
     * @return mixed
     */
    public function getFormValue(array $converterAttributes, $value)
    {
        return $value;
    }
}
