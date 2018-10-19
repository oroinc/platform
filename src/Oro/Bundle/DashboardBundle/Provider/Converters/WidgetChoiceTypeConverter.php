<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;

class WidgetChoiceTypeConverter extends ConfigValueConverterAbstract
{
    const ALL_ITEMS = 'all';

    /**
     * {@inheritdoc}
     */
    public function getConvertedValue(array $widgetConfig, $value = null, array $config = [], array $options = [])
    {
        if ($value === null) {
            return $this->getDefaultChoices($config);
        }

        return parent::getConvertedValue($widgetConfig, $value, $config, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormValue(array $config, $value)
    {
        if ($value === null) {
            return $this->getDefaultChoices($config);
        }

        return parent::getFormValue($config, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getViewValue($value)
    {
        return implode(',', (array)$value);
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function getDefaultChoices(array $config)
    {
        if ($config['converter_attributes']['default_selected'] === self::ALL_ITEMS) {
            $values = [];
            foreach ($config['options']['choices'] as $option) {
                $values = array_merge($values, array_values($option));
            }

            return $values;
        }

        return $config['converter_attributes']['default_selected'];
    }
}
