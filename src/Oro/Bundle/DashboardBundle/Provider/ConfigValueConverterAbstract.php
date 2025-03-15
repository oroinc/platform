<?php

namespace Oro\Bundle\DashboardBundle\Provider;

/**
 * Represents the dashboard widget configuration converter
 * that provides a way to get configuration values for dashboard widgets.
 */
abstract class ConfigValueConverterAbstract
{
    /**
     * Returns converted value.
     */
    public function getConvertedValue(
        array $widgetConfig,
        mixed $value = null,
        array $config = [],
        array $options = []
    ): mixed {
        return $value;
    }

    /**
     * Returns view value.
     */
    public function getViewValue(mixed $value): mixed
    {
        return (string)$value;
    }

    /**
     * Returns form value.
     */
    public function getFormValue(array $config, mixed $value): mixed
    {
        return $value;
    }
}
