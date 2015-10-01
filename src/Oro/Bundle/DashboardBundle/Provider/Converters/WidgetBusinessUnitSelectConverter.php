<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;

/**
 * Class WidgetBusinessUnitSelectConverter
 * @package Oro\Bundle\DashboardBundle\Provider\Converters
 */
class WidgetBusinessUnitSelectConverter extends ConfigValueConverterAbstract
{
    /**
     * @param mixed $value
     * @return string
     */
    public function getViewValue($value)
    {
        $value = is_array($value) ? $value : [$value];

        return implode('; ', $value);
    }
}
