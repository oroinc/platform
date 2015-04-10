<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;

class WidgetTitleConverter extends ConfigValueConverterAbstract
{
    /**
     * {@inheritdoc}
     */
    public function getConvertedValue(array $widgetConfig, $value = null, array $config = [], array $options = [])
    {
        if (!empty($value) && !$value['useDefault']) {
            return $value['title'];
        }

        return $widgetConfig['label'];
    }
}
