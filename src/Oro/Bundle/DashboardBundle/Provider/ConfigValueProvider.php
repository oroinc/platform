<?php

namespace Oro\Bundle\DashboardBundle\Provider;

class ConfigValueProvider
{
    /** @var array */
    protected $converters;

    /**
     * @param string                       $formType
     * @param ConfigValueConverterAbstract $converter
     */
    public function addConverter($formType, ConfigValueConverterAbstract $converter)
    {
        $this->converters[$formType] = $converter;
    }

    /**
     * @param string $formType
     * @param mixed  $value
     * @return mixed
     */
    public function getConvertedValue($widgetConfig, $formType, $value = null, $converterAttributes = [], $options = [])
    {
        if (in_array($formType, array_keys($this->converters))) {
            return $this->converters[$formType]->getConvertedValue($widgetConfig, $value, $converterAttributes, $options);
        }

        return $value;
    }

    /**
     * @param $formType
     * @param $value
     * @return string
     */
    public function getViewValue($formType, $value)
    {
        if (in_array($formType, array_keys($this->converters))) {
            return $this->converters[$formType]->getViewValue($value);
        }

        return $value;
    }
}
