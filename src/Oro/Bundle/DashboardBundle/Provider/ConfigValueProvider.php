<?php

namespace Oro\Bundle\DashboardBundle\Provider;


class ConfigValueProvider
{
    /** @var array */
    protected $converters;

    /**
     * @param string               $formType
     * @param ConfigValueConverter $converter
     */
    public function addConverter($formType, ConfigValueConverter $converter)
    {
        $this->converters[$formType] = $converter;
    }

    /**
     * @param string $formType
     * @param mixed $value
     * @return mixed
     */
    public function getConvertedValue($formType, $value)
    {
        if(in_array($formType, array_keys($this->converters))) {
            return $this->converters[$formType]->getConvertedValue($value);
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
        if(in_array($formType, array_keys($this->converters))) {
            return $this->converters[$formType]->getViewValue($value);
        }

        return $value;
    }
}
