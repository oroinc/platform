<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;

/**
 * The dashboard widget configuration converter for choice a value from a list of predefined values.
 */
class WidgetChoiceTypeConverter extends ConfigValueConverterAbstract
{
    private const string ALL_ITEMS = 'all';

    #[\Override]
    public function getConvertedValue(
        array $widgetConfig,
        mixed $value = null,
        array $config = [],
        array $options = []
    ): mixed {
        if (null === $value) {
            return $this->getDefaultChoices($config);
        }

        return parent::getConvertedValue($widgetConfig, $value, $config, $options);
    }

    #[\Override]
    public function getFormValue(array $config, mixed $value): mixed
    {
        if (null === $value) {
            return $this->getDefaultChoices($config);
        }

        return parent::getFormValue($config, $value);
    }

    #[\Override]
    public function getViewValue(mixed $value): mixed
    {
        return implode(',', (array)$value);
    }

    protected function getDefaultChoices(array $config): mixed
    {
        if (self::ALL_ITEMS === $config['converter_attributes']['default_selected']) {
            $values = [];
            foreach ($config['options']['choices'] as $option) {
                $values[] = array_values($option);
            }

            return array_merge(...$values);
        }

        return $config['converter_attributes']['default_selected'];
    }
}
