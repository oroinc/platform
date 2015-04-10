<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Provider;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;

class TestConverter extends ConfigValueConverterAbstract
{
    /**
     * {@inheritdoc}
     */
    public function getConvertedValue(array $widgetConfig, $value = null)
    {
        return 'test value';
    }

    /**
     * {@inheritdoc}
     */
    public function getViewValue($value)
    {
        return 'test view value';
    }
}
