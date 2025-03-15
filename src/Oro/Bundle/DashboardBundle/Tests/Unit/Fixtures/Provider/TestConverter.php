<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Provider;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;

class TestConverter extends ConfigValueConverterAbstract
{
    #[\Override]
    public function getConvertedValue(
        array $widgetConfig,
        mixed $value = null,
        array $config = [],
        array $options = []
    ): mixed {
        return 'test value';
    }

    #[\Override]
    public function getViewValue(mixed $value): mixed
    {
        return 'test view value';
    }

    #[\Override]
    public function getFormValue(array $config, mixed $value): mixed
    {
        return $config['value'];
    }
}
