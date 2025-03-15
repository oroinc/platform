<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;

/**
 * The dashboard widget configuration converter for enter a widget title.
 */
class WidgetTitleConverter extends ConfigValueConverterAbstract
{
    #[\Override]
    public function getConvertedValue(
        array $widgetConfig,
        mixed $value = null,
        array $config = [],
        array $options = []
    ): mixed {
        if (!empty($value) && !$value['useDefault']) {
            return $value['title'];
        }

        return $widgetConfig['label'];
    }
}
