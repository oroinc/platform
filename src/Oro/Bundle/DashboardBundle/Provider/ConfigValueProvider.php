<?php

namespace Oro\Bundle\DashboardBundle\Provider;

use Psr\Container\ContainerInterface;

/**
 * Provides a way to get configuration values for dashboard widgets.
 */
class ConfigValueProvider
{
    public function __construct(
        private ContainerInterface $converters
    ) {
    }

    /**
     * Returns converted value for the given form type.
     */
    public function getConvertedValue(
        array $widgetConfig,
        string $formType,
        mixed $value = null,
        array $config = [],
        array $options = []
    ): mixed {
        $converter = $this->getConverter($formType);
        if (null === $converter) {
            return $value;
        }

        return $converter->getConvertedValue($widgetConfig, $value, $config, $options);
    }

    /**
     * Returns view value for the given form type.
     */
    public function getViewValue(string $formType, mixed $value): mixed
    {
        $converter = $this->getConverter($formType);
        if (null === $converter) {
            return $value;
        }

        return $converter->getViewValue($value);
    }

    /**
     * Returns form value for the given form type.
     */
    public function getFormValue(string $formType, array $config, mixed $value): mixed
    {
        $converter = $this->getConverter($formType);
        if (null === $converter) {
            return $value;
        }

        return $converter->getFormValue($config, $value);
    }

    private function getConverter(string $formType): ?ConfigValueConverterAbstract
    {
        return $this->converters->has($formType)
            ? $this->converters->get($formType)
            : null;
    }
}
