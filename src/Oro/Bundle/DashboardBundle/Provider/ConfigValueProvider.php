<?php

namespace Oro\Bundle\DashboardBundle\Provider;

use Psr\Container\ContainerInterface;

/**
 * Provides a way to get configuration values for dashboard widgets.
 */
class ConfigValueProvider
{
    /** @var ContainerInterface */
    private $converters;

    public function __construct(ContainerInterface $converters)
    {
        $this->converters = $converters;
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
        $converter = $this->getConverter($formType);
        if (null !== $converter) {
            return $converter->getConvertedValue($widgetConfig, $value, $config, $options);
        }

        return $value;
    }

    /**
     * @param string $formType
     * @param mixed  $value
     *
     * @return string
     */
    public function getViewValue($formType, $value)
    {
        $converter = $this->getConverter($formType);
        if (null !== $converter) {
            return $converter->getViewValue($value);
        }

        return $value;
    }

    /**
     * @param string $formType
     * @param array  $config
     * @param mixed  $value
     *
     * @return mixed
     */
    public function getFormValue($formType, $config, $value)
    {
        $converter = $this->getConverter($formType);
        if (null !== $converter) {
            return $converter->getFormValue($config, $value);
        }

        return $value;
    }

    private function getConverter(string $formType): ?ConfigValueConverterAbstract
    {
        if (!$this->converters->has($formType)) {
            return null;
        }

        return $this->converters->get($formType);
    }
}
