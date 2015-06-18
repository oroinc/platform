<?php

namespace Oro\Bundle\DashboardBundle\Provider;

class ConfigValueProvider
{
    /** @var ConfigValueConverterAbstract[] */
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
     * @param array  $widgetConfig
     * @param string $formType
     * @param null   $value
     * @param array  $config
     * @param array  $options
     *
     * @return string
     */
    public function getConvertedValue($widgetConfig, $formType, $value = null, $config = [], $options = [])
    {
        if (in_array($formType, array_keys($this->converters))) {
            return $this->converters[$formType]->getConvertedValue($widgetConfig, $value, $config, $options);
        }

        return $value;
    }

    /**
     * @param $formType
     * @param $value
     *
     * @return string
     */
    public function getViewValue($formType, $value)
    {
        if (in_array($formType, array_keys($this->converters))) {
            return $this->converters[$formType]->getViewValue($value);
        }

        return $value;
    }

    /**
     * @param $formType
     * @param $config
     * @param $value
     *
     * @return mixed
     */
    public function getFormValue($formType, $config, $value)
    {
        if (in_array($formType, array_keys($this->converters))) {
            return $this->converters[$formType]->getFormValue($config, $value);
        }

        return $value;
    }
}
